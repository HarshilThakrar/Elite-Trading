<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\InventoryLedger;
use App\Services\AccountingEngine;
use App\Services\FinancialYearService;
use App\Services\AccountingSettingsService;
use App\Services\GstEngine;
use Illuminate\Support\Facades\DB;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class DebitNoteController extends Controller
{
    protected $accountingEngine;
    protected $fyService;
    protected $settingsService;
    protected $gstEngine;

    public function __construct(
        AccountingEngine $accountingEngine,
        FinancialYearService $fyService,
        AccountingSettingsService $settingsService,
        GstEngine $gstEngine
    ) {
        $this->accountingEngine = $accountingEngine;
        $this->fyService = $fyService;
        $this->settingsService = $settingsService;
        $this->gstEngine = $gstEngine;
    }

    public function index(Request $request)
    {
        $query = Voucher::where('type', 'Debit Note')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('vendor_id') && $request->vendor_id) {
            $vendorId = $request->vendor_id;
            $query->whereJsonContains('metadata->vendor_id', (string)$vendorId);
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        $vendors = Vendor::where('status', true)->orderBy('company_name')->get();
        
        return view('admin.debit-notes.index', compact('vouchers', 'vendors'));
    }

    public function selectVendorAndPurchaseVoucher(Request $request)
    {
        $vendors = Vendor::where('status', true)->orderBy('company_name')->get();
        $selectedVendor = null;
        $purchaseVouchers = collect();

        if ($request->has('vendor_id') && $request->vendor_id) {
            $selectedVendor = Vendor::findOrFail($request->vendor_id);
            // Get all Posted Purchase Vouchers for this vendor
            $purchaseVouchers = Voucher::where('type', 'Purchase')
                ->where('status', 'Posted')
                ->whereJsonContains('metadata->vendor_id', (string)$selectedVendor->id)
                ->get()
                ->filter(function($voucher) {
                    $hasReturnable = false;
                    $lines = $voucher->metadata['lines'] ?? [];
                    foreach ($lines as $line) {
                        $pi = PurchaseItem::find($line['purchase_item_id']);
                        if ($pi) {
                            $invoiced = (float)($pi->invoiced_qty ?? 0);
                            $returned = (float)($pi->returned_qty ?? 0);
                            if (($invoiced - $returned) > 0) {
                                $hasReturnable = true;
                                break;
                            }
                        }
                    }
                    return $hasReturnable;
                });
        }

        return view('admin.debit-notes.create-select', compact('vendors', 'selectedVendor', 'purchaseVouchers'));
    }

    public function create(Voucher $purchaseVoucher)
    {
        if ($purchaseVoucher->type !== 'Purchase' || $purchaseVoucher->status !== 'Posted') {
            return redirect()->route('debit-notes.select')->with('error', 'Invalid Purchase Voucher selected.');
        }

        $vendorId = $purchaseVoucher->metadata['vendor_id'] ?? null;
        $vendor = Vendor::find($vendorId);
        $settings = $this->settingsService->getSettings();
        
        $lines = $purchaseVoucher->metadata['lines'] ?? [];
        $itemsWithReturnable = [];

        foreach ($lines as $line) {
            $pi = PurchaseItem::with('product')->find($line['purchase_item_id']);
            if (!$pi) continue;

            $invoiced = (float)($pi->invoiced_qty ?? 0);
            $returned = (float)($pi->returned_qty ?? 0);
            $returnable = $invoiced - $returned;

            if ($returnable > 0) {
                $pi->returnable_qty = $returnable;
                $pi->unit_price = $line['unit_price']; // Rate exactly from Purchase Voucher
                $itemsWithReturnable[] = $pi;
            }
        }

        if (count($itemsWithReturnable) === 0) {
            return redirect()->route('debit-notes.select')->with('error', 'No returnable quantity left for this Purchase Voucher.');
        }

        return view('admin.debit-notes.create', compact('purchaseVoucher', 'vendor', 'itemsWithReturnable', 'settings'));
    }

    public function store(Request $request, Voucher $purchaseVoucher)
    {
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.return_qty' => 'nullable|numeric|min:0',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        $hasReturn = false;
        foreach ($request->items as $itemData) {
            if (isset($itemData['return_qty']) && $itemData['return_qty'] > 0) {
                $hasReturn = true;
                break;
            }
        }

        if (!$hasReturn) {
            return back()->with('error', 'Please enter a return quantity for at least one item.')->withInput();
        }

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $isInterState = false; // Add specific Vendor vs Company logic if needed
            
            $lines = [];
            $totalTaxable = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;

            foreach ($request->items as $itemData) {
                $qty = (float)($itemData['return_qty'] ?? 0);
                if ($qty <= 0) continue;
                
                // Use lockForUpdate to ensure idempotency and prevent over-returning
                $purchaseItem = PurchaseItem::with('product')->lockForUpdate()->findOrFail($itemData['purchase_item_id']);
                
                $invoiced = (float)($purchaseItem->invoiced_qty ?? 0);
                $returned = (float)($purchaseItem->returned_qty ?? 0);
                $availableToReturn = $invoiced - $returned;
                
                if ($qty > $availableToReturn) {
                    throw new Exception("Return quantity cannot exceed the returnable quantity for product: " . ($purchaseItem->product->item_name ?? 'Unknown'));
                }

                // Check physical stock
                $product = Product::lockForUpdate()->find($purchaseItem->product_id);
                $negativeStockAllowed = $this->settingsService->getSettings()->allow_negative_stock ?? false;
                
                if (!$negativeStockAllowed && $qty > $product->available_stock) {
                    throw new Exception("Insufficient physical stock for product: {$product->item_name}. Return quantity ({$qty}) exceeds available stock ({$product->available_stock}).");
                }

                // Find original rate from the Purchase Voucher metadata
                $originalRate = 0;
                foreach ($purchaseVoucher->metadata['lines'] ?? [] as $pvLine) {
                    if ($pvLine['purchase_item_id'] == $purchaseItem->id) {
                        $originalRate = $pvLine['unit_price'];
                        break;
                    }
                }

                if ($originalRate <= 0) {
                    throw new Exception("Original purchase rate not found for product: " . $product->item_name);
                }

                $amount = round($qty * $originalRate, 2);
                $totalTaxable += $amount;

                $itemCgst = 0; $itemSgst = 0; $itemIgst = 0;
                if ($product->gst_rate > 0) {
                    $gstCalc = $this->gstEngine->calculate($amount, $product->gst_rate, $isInterState);
                    $itemCgst = $gstCalc['cgst'];
                    $itemSgst = $gstCalc['sgst'];
                    $itemIgst = $gstCalc['igst'];
                    
                    $totalCgst += $itemCgst;
                    $totalSgst += $itemSgst;
                    $totalIgst += $itemIgst;
                }

                $lines[] = [
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $product->id,
                    'return_qty' => $qty,
                    'unit_price' => $originalRate,
                    'amount' => $amount,
                    'cgst' => $itemCgst,
                    'sgst' => $itemSgst,
                    'igst' => $itemIgst,
                ];
            }

            $grandTotal = $totalTaxable + $totalCgst + $totalSgst + $totalIgst;

            $metadata = [
                'vendor_id' => $purchaseVoucher->metadata['vendor_id'] ?? null,
                'original_purchase_voucher_id' => (string)$purchaseVoucher->id,
                'original_purchase_voucher_number' => $purchaseVoucher->voucher_number,
                'total_taxable' => $totalTaxable,
                'total_cgst' => $totalCgst,
                'total_sgst' => $totalSgst,
                'total_igst' => $totalIgst,
                'grand_total' => $grandTotal,
                'lines' => $lines,
                'reason' => $request->reason
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-DN-' . time(),
                    'date' => $request->date,
                    'type' => 'Debit Note',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'reference_id' => $purchaseVoucher->id,
                    'reference_type' => Voucher::class,
                    'draft_data' => [
                        'lines' => $lines,
                        'metadata' => $metadata
                    ],
                    'metadata' => $metadata
                ]);
                
                DB::commit();
                return redirect()->route('debit-notes.index')->with('success', 'Debit Note saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Debit Note',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'reference_id' => $purchaseVoucher->id,
                    'reference_type' => Voucher::class,
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $inventoryGroup = \App\Models\AccountGroup::where('name', 'Current Assets')->first();
                $inventoryLedger = \App\Models\Ledger::firstOrCreate(
                    ['name' => 'Stock in Hand'],
                    ['account_group_id' => $inventoryGroup->id, 'is_system' => true]
                );

                // Update Quantities and Physical Stock
                foreach ($lines as $line) {
                    $pi = PurchaseItem::find($line['purchase_item_id']);
                    $pi->returned_qty += $line['return_qty'];
                    $pi->save();
                    
                    $product = Product::find($line['product_id']);
                    $product->available_stock -= $line['return_qty'];
                    $product->save();

                    // Inventory Ledger OUT entry
                    InventoryLedger::create([
                        'product_id' => $product->id,
                        'type' => 'OUT',
                        'quantity' => $line['return_qty'],
                        'balance_after' => $product->available_stock,
                        'reference_type' => 'Debit Note', // Will update later with Voucher ID
                        'notes' => 'Purchase Return via Debit Note'
                    ]);
                }

                // Accounting Logic (Debit Vendor, Credit Stock, Credit GST)
                $vendorId = $purchaseVoucher->metadata['vendor_id'] ?? null;
                $vendorLedger = \App\Models\Ledger::where('type', 'vendor')->where('reference_id', $vendorId)->first();
                if (!$vendorLedger) {
                    throw new Exception("Vendor Ledger not found.");
                }

                $cgstLedger = \App\Models\Ledger::where('name', 'CGST A/c')->first();
                $sgstLedger = \App\Models\Ledger::where('name', 'SGST A/c')->first();
                $igstLedger = \App\Models\Ledger::where('name', 'IGST A/c')->first();

                // Vendor gets debited (liability reduced)
                $entries = [
                    [
                        'ledger_id' => $vendorLedger->id,
                        'type' => 'Dr',
                        'amount' => $grandTotal,
                        'narration' => 'Debit Note / Purchase Return against ' . $purchaseVoucher->voucher_number
                    ],
                    [
                        'ledger_id' => $inventoryLedger->id,
                        'type' => 'Cr',
                        'amount' => $totalTaxable,
                        'narration' => 'Stock Out (Purchase Return)'
                    ]
                ];
                
                if ($totalCgst > 0 && $cgstLedger) {
                    $entries[] = ['ledger_id' => $cgstLedger->id, 'type' => 'Cr', 'amount' => $totalCgst, 'narration' => 'ITC Reversal on CGST'];
                }
                if ($totalSgst > 0 && $sgstLedger) {
                    $entries[] = ['ledger_id' => $sgstLedger->id, 'type' => 'Cr', 'amount' => $totalSgst, 'narration' => 'ITC Reversal on SGST'];
                }
                if ($totalIgst > 0 && $igstLedger) {
                    $entries[] = ['ledger_id' => $igstLedger->id, 'type' => 'Cr', 'amount' => $totalIgst, 'narration' => 'ITC Reversal on IGST'];
                }

                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                // Update InventoryLedger records with new Voucher ID
                InventoryLedger::where('reference_type', 'Debit Note')->whereNull('reference_id')
                               ->update(['reference_type' => Voucher::class, 'reference_id' => $voucher->id]);
                
                DB::commit();
                return redirect()->route('debit-notes.index')->with('success', 'Debit Note posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Debit Note')->with(['entries.ledger', 'financialYear', 'reference'])->findOrFail($id);
        return view('admin.debit-notes.show', compact('voucher'));
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Debit Note')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('debit-notes.index')->with('success', 'Draft Debit Note deleted.');
            } else {
                throw new Exception("Cancellation of posted Debit Notes requires a comprehensive reversal mechanism to correctly restore physical stock and vendor payable, which is currently not fully implemented.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function generatePdf($id, Request $request)
    {
        $voucher = Voucher::where('type', 'Debit Note')->with(['entries.ledger', 'financialYear', 'reference'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.debit-notes.pdf', compact('voucher'));
        $safeVch = str_replace(['/', '\\', ' '], ['-', '-', '_'], $voucher->voucher_number ?? (string)$voucher->id);
        return $pdf->download('Debit_Note_' . $safeVch . '.pdf');
    }
}
