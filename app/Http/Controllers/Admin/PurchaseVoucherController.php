<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Voucher;
use App\Models\Vendor;
use App\Models\Ledger;
use App\Models\PurchaseItem;
// Removed GRN import
use App\Services\AccountingEngine;
use App\Services\FinancialYearService;
use App\Services\AccountingSettingsService;
use App\Services\GstEngine;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseVoucherController extends Controller
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
        $query = Voucher::where('type', 'Purchase')->with(['financialYear', 'entries.ledger', 'reference']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('metadata->vendor_invoice_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('vendor_id') && $request->vendor_id) {
            $vendorId = $request->vendor_id;
            $query->whereJsonContains('metadata->vendor_id', (string)$vendorId);
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        $vendors = Vendor::where('status', true)->orderBy('company_name')->get();
        
        return view('admin.purchase-vouchers.index', compact('vouchers', 'vendors'));
    }

    public function selectPo(Request $request)
    {
        $vendors = Vendor::where('status', true)->orderBy('company_name')->get();
        $selectedVendor = null;
        $purchases = collect();

        if ($request->has('vendor_id') && $request->vendor_id) {
            $selectedVendor = Vendor::findOrFail($request->vendor_id);
            // Get Approved or Partially Invoiced POs
            $purchases = Purchase::where('vendor_id', $selectedVendor->id)
                ->whereIn('status', ['Approved', 'Received', 'Partially Received'])
                ->with('items')
                ->get()
                ->filter(function($po) {
                    // Check if PO has available invoiceable qty based directly on PO qty
                    $hasInvoiceableQty = false;
                    foreach ($po->items as $item) {
                        $ordered = (float)($item->quantity ?? 0);
                        $invoiced = (float)($item->invoiced_qty ?? 0);
                        if ($ordered > $invoiced) {
                            $hasInvoiceableQty = true;
                            break;
                        }
                    }
                    return $hasInvoiceableQty;
                });
        }

        return view('admin.purchase-vouchers.select-po', compact('vendors', 'selectedVendor', 'purchases'));
    }

    public function create(Purchase $purchase)
    {
        $vendor = $purchase->vendor;
        $settings = $this->settingsService->getSettings();
        
        $itemsWithPending = [];
        foreach ($purchase->items as $item) {
            $ordered = (float)($item->quantity ?? 0);
            $invoicedSoFar = (float)($item->invoiced_qty ?? 0);
            $availableToInvoice = $ordered - $invoicedSoFar;
            
            if ($availableToInvoice > 0) {
                $item->received_so_far = $ordered; // Dummy mapping for view compatibility
                $item->invoiced_so_far = $invoicedSoFar;
                $item->available_to_invoice = $availableToInvoice;
                $itemsWithPending[] = $item;
            }
        }

        if (count($itemsWithPending) === 0) {
            return redirect()->route('purchase-vouchers.select-po')->with('error', 'No pending quantities available to invoice for this Purchase Order.');
        }

        return view('admin.purchase-vouchers.create', compact('purchase', 'vendor', 'itemsWithPending', 'settings'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        $rules = [
            'date' => 'required|date',
            'vendor_invoice_number' => 'required|string|max:100',
            'vendor_invoice_date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.invoiced_qty' => 'nullable|numeric|min:0',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        $hasInvoice = false;
        foreach ($request->items as $itemData) {
            if (isset($itemData['invoiced_qty']) && $itemData['invoiced_qty'] > 0) {
                $hasInvoice = true;
                break;
            }
        }

        if (!$hasInvoice) {
            return back()->with('error', 'Please enter invoiced quantity for at least one item.')->withInput();
        }

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            
            // Check for duplicate Vendor Invoice
            $duplicateCheck = Voucher::where('type', 'Purchase')
                ->where('status', '!=', 'Cancelled')
                ->where('financial_year_id', $finYear->id)
                ->whereJsonContains('metadata->vendor_id', (string)$purchase->vendor_id)
                ->whereJsonContains('metadata->vendor_invoice_number', $request->vendor_invoice_number)
                ->exists();
                
            if ($duplicateCheck) {
                throw new Exception("Vendor invoice number already exists for this vendor in this financial year.");
            }

            $isInterState = false; // Add logic if Vendor state != Company state
            
            $lines = [];
            $totalTaxable = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;

            foreach ($request->items as $itemData) {
                $qty = (float)($itemData['invoiced_qty'] ?? 0);
                if ($qty <= 0) continue;
                
                $purchaseItem = PurchaseItem::with('product')->findOrFail($itemData['purchase_item_id']);
                
                $ordered = (float)($purchaseItem->quantity ?? 0);
                $invoiced = (float)($purchaseItem->invoiced_qty ?? 0);
                $available = $ordered - $invoiced;
                
                if ($qty > $available) {
                    throw new Exception("Purchase quantity cannot exceed the available ordered quantity for product: " . ($purchaseItem->product->item_name ?? 'Unknown'));
                }

                $amount = round($qty * $purchaseItem->unit_price, 2);
                $totalTaxable += $amount;

                $itemCgst = 0; $itemSgst = 0; $itemIgst = 0;
                if ($purchaseItem->product && $purchaseItem->product->gst_rate > 0) {
                    $gstCalc = $this->gstEngine->calculate($amount, $purchaseItem->product->gst_rate, $isInterState);
                    $itemCgst = $gstCalc['cgst'];
                    $itemSgst = $gstCalc['sgst'];
                    $itemIgst = $gstCalc['igst'];
                    
                    $totalCgst += $itemCgst;
                    $totalSgst += $itemSgst;
                    $totalIgst += $itemIgst;
                }

                $lines[] = [
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'invoiced_qty' => $qty,
                    'unit_price' => $purchaseItem->unit_price,
                    'amount' => $amount,
                    'cgst' => $itemCgst,
                    'sgst' => $itemSgst,
                    'igst' => $itemIgst,
                ];
            }

            $grandTotal = $totalTaxable + $totalCgst + $totalSgst + $totalIgst;

            $metadata = [
                'vendor_id' => (string)$purchase->vendor_id,
                'purchase_id' => (string)$purchase->id,
                'vendor_invoice_number' => $request->vendor_invoice_number,
                'vendor_invoice_date' => $request->vendor_invoice_date,
                'total_taxable' => $totalTaxable,
                'total_cgst' => $totalCgst,
                'total_sgst' => $totalSgst,
                'total_igst' => $totalIgst,
                'grand_total' => $grandTotal,
                'lines' => $lines
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-PV-' . time(),
                    'date' => $request->date,
                    'type' => 'Purchase',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'draft_data' => [
                        'lines' => $lines,
                        'metadata' => $metadata
                    ],
                    'metadata' => $metadata
                ]);
                
                DB::commit();
                return redirect()->route('purchase-vouchers.index')->with('success', 'Purchase Voucher saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Purchase',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                // Update invoiced quantities and physical stock
                $isPoComplete = true;
                foreach ($lines as $line) {
                    $pi = PurchaseItem::find($line['purchase_item_id']);
                    $pi->invoiced_qty += $line['invoiced_qty'];
                    $pi->save();
                    
                    if ($pi->invoiced_qty < $pi->quantity) {
                        $isPoComplete = false;
                    }

                    // Update Physical Stock Directly
                    $product = \App\Models\Product::find($line['product_id']);
                    if ($product) {
                        $product->available_stock += $line['invoiced_qty'];
                        $product->save();

                        \App\Models\InventoryLedger::create([
                            'product_id' => $product->id,
                            'type' => 'IN',
                            'quantity' => $line['invoiced_qty'],
                            'balance_after' => $product->available_stock,
                            'reference_type' => Voucher::class,
                            'reference_id' => null, // Updated below
                            'notes' => 'Direct Purchase Invoice'
                        ]);
                    }
                }

                if ($isPoComplete) {
                    $purchase->status = 'Completed';
                    $purchase->save();
                }

                // Accounting logic
                $inventoryGroup = \App\Models\AccountGroup::where('name', 'Current Assets')->first();
                $inventoryLedger = \App\Models\Ledger::firstOrCreate(
                    ['name' => 'Stock in Hand'],
                    ['account_group_id' => $inventoryGroup->id, 'is_system' => true]
                );
                
                $vendorLedger = $purchase->vendor->ledger;
                if (!$vendorLedger) {
                    throw new Exception("Vendor Ledger not found for Vendor: {$purchase->vendor->company_name}");
                }

                $cgstLedger = \App\Models\Ledger::where('name', 'CGST A/c')->first();
                $sgstLedger = \App\Models\Ledger::where('name', 'SGST A/c')->first();
                $igstLedger = \App\Models\Ledger::where('name', 'IGST A/c')->first();

                $entries = [
                    [
                        'ledger_id' => $inventoryLedger->id,
                        'type' => 'Dr',
                        'amount' => $totalTaxable,
                        'narration' => 'Purchase Value'
                    ],
                    [
                        'ledger_id' => $vendorLedger->id,
                        'type' => 'Cr',
                        'amount' => $grandTotal,
                        'narration' => 'Vendor Payable for Invoice ' . $request->vendor_invoice_number
                    ]
                ];
                
                if ($totalCgst > 0 && $cgstLedger) {
                    $entries[] = ['ledger_id' => $cgstLedger->id, 'type' => 'Dr', 'amount' => $totalCgst, 'narration' => 'ITC on CGST'];
                }
                if ($totalSgst > 0 && $sgstLedger) {
                    $entries[] = ['ledger_id' => $sgstLedger->id, 'type' => 'Dr', 'amount' => $totalSgst, 'narration' => 'ITC on SGST'];
                }
                if ($totalIgst > 0 && $igstLedger) {
                    $entries[] = ['ledger_id' => $igstLedger->id, 'type' => 'Dr', 'amount' => $totalIgst, 'narration' => 'ITC on IGST'];
                }

                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                // Update InventoryLedger records with new Voucher ID
                \App\Models\InventoryLedger::where('reference_type', Voucher::class)
                    ->whereNull('reference_id')
                    ->where('notes', 'Direct Purchase Invoice')
                    ->update(['reference_id' => $voucher->id]);
                
                DB::commit();
                return redirect()->route('purchase-vouchers.index')->with('success', 'Purchase Voucher posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Purchase')->with(['entries.ledger', 'financialYear', 'reference.vendor', 'reference.items.product'])->findOrFail($id);
        return view('admin.purchase-vouchers.show', compact('voucher'));
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Purchase')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('purchase-vouchers.index')->with('success', 'Draft Purchase Voucher deleted.');
            } else {
                throw new Exception("Cancellation of posted purchase vouchers requires full reversal logic, which is currently not supported without debit notes.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function generatePdf($id, Request $request)
    {
        $voucher = Voucher::where('type', 'Purchase')->with(['entries.ledger', 'financialYear', 'reference.vendor', 'reference.items.product'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.purchase-vouchers.pdf', compact('voucher'));
        $filename = 'Purchase_Voucher_' . str_replace('/', '_', $voucher->voucher_number) . '.pdf';
        return $pdf->download($filename);
    }
}
