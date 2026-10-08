<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\InventoryLedger;
use App\Services\AccountingEngine;
use App\Services\FinancialYearService;
use App\Services\AccountingSettingsService;
use App\Services\GstEngine;
use Illuminate\Support\Facades\DB;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNoteController extends Controller
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
        $query = Voucher::where('type', 'Credit Note')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('customer_id') && $request->customer_id) {
            $customerId = $request->customer_id;
            $query->whereJsonContains('metadata->customer_id', (string)$customerId);
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        $customers = Customer::where('status', true)->orderBy('company_name')->get();
        
        return view('admin.credit-notes.index', compact('vouchers', 'customers'));
    }

    public function selectCustomerAndSalesVoucher(Request $request)
    {
        $customers = Customer::where('status', true)->orderBy('company_name')->get();
        $selectedCustomer = null;
        $salesVouchers = collect();

        if ($request->has('customer_id') && $request->customer_id) {
            $selectedCustomer = Customer::findOrFail($request->customer_id);
            
            // Get all Posted Sales Vouchers (Invoices) for this customer
            $salesVouchers = Voucher::where('type', 'Sales')
                ->where('status', 'Posted')
                ->whereHasMorph('reference', [\App\Models\Invoice::class], function($q) use ($selectedCustomer) {
                    $q->whereHas('sale', function($q2) use ($selectedCustomer) {
                        $q2->where('customer_id', $selectedCustomer->id);
                    });
                })
                ->get()
                ->filter(function($voucher) {
                    $hasReturnable = false;
                    $invoice = $voucher->reference; // Invoice model
                    if ($invoice) {
                        foreach ($invoice->items as $item) {
                            $saleItem = SaleItem::where('sale_id', $invoice->sale_id)
                                ->where('product_id', $item->product_id)
                                ->first();
                            
                            if ($saleItem) {
                                $invoiced = (float)($saleItem->invoiced_qty ?? 0);
                                $returned = (float)($saleItem->returned_qty ?? 0);
                                if (($invoiced - $returned) > 0) {
                                    $hasReturnable = true;
                                    break;
                                }
                            }
                        }
                    }
                    return $hasReturnable;
                });
        }

        return view('admin.credit-notes.create-select', compact('customers', 'selectedCustomer', 'salesVouchers'));
    }

    public function create(Voucher $salesVoucher)
    {
        if ($salesVoucher->type !== 'Sales' || $salesVoucher->status !== 'Posted') {
            return redirect()->route('credit-notes.select')->with('error', 'Invalid Sales Voucher selected.');
        }

        $invoice = $salesVoucher->reference;
        if (!$invoice || !$invoice->sale) {
            return redirect()->route('credit-notes.select')->with('error', 'Original Invoice not found.');
        }

        $customer = $invoice->sale->customer;
        $settings = $this->settingsService->getSettings();
        
        $itemsWithReturnable = [];

        foreach ($invoice->items as $item) {
            $saleItem = SaleItem::with('product')->where('sale_id', $invoice->sale_id)->where('product_id', $item->product_id)->first();
            if (!$saleItem) continue;

            $invoiced = (float)($saleItem->invoiced_qty ?? 0);
            $returned = (float)($saleItem->returned_qty ?? 0);
            $returnable = $invoiced - $returned;

            if ($returnable > 0) {
                $saleItem->returnable_qty = $returnable;
                $saleItem->invoice_rate = $item->unit_price; 
                $itemsWithReturnable[] = $saleItem;
            }
        }

        if (count($itemsWithReturnable) === 0) {
            return redirect()->route('credit-notes.select')->with('error', 'No returnable quantity left for this Sales Voucher.');
        }

        return view('admin.credit-notes.create', compact('salesVoucher', 'customer', 'itemsWithReturnable', 'settings', 'invoice'));
    }

    public function store(Request $request, Voucher $salesVoucher)
    {
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
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

        $invoice = $salesVoucher->reference;
        if (!$invoice || !$invoice->sale) {
            return back()->with('error', 'Original Invoice not found.')->withInput();
        }

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $isInterState = false; // Hardcoded per prompt's existing structure
            
            $lines = [];
            $totalTaxable = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;

            foreach ($request->items as $itemData) {
                $qty = (float)($itemData['return_qty'] ?? 0);
                if ($qty <= 0) continue;
                
                $saleItem = SaleItem::with('product')->lockForUpdate()->findOrFail($itemData['sale_item_id']);
                
                $invoiced = (float)($saleItem->invoiced_qty ?? 0);
                $returned = (float)($saleItem->returned_qty ?? 0);
                $availableToReturn = $invoiced - $returned;
                
                if ($qty > $availableToReturn) {
                    throw new Exception("Return quantity cannot exceed the returnable quantity for product: " . ($saleItem->product->item_name ?? 'Unknown'));
                }

                $originalRate = 0;
                foreach ($invoice->items as $invItem) {
                    if ($invItem->product_id == $saleItem->product_id) {
                        $originalRate = $invItem->unit_price;
                        break;
                    }
                }

                if ($originalRate <= 0) {
                    throw new Exception("Original invoice rate not found for product: " . $saleItem->product->item_name);
                }

                $amount = round($qty * $originalRate, 2);
                $totalTaxable += $amount;

                $itemCgst = 0; $itemSgst = 0; $itemIgst = 0;
                $product = $saleItem->product;
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
                    'sale_item_id' => $saleItem->id,
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
                'customer_id' => (string)$invoice->sale->customer_id,
                'original_sales_voucher_id' => (string)$salesVoucher->id,
                'original_sales_voucher_number' => $salesVoucher->voucher_number,
                'original_invoice_number' => $invoice->invoice_number,
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
                    'voucher_number' => 'DRAFT-CN-' . time(),
                    'date' => $request->date,
                    'type' => 'Credit Note',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'reference_id' => $salesVoucher->id,
                    'reference_type' => Voucher::class,
                    'draft_data' => [
                        'lines' => $lines,
                        'metadata' => $metadata
                    ],
                    'metadata' => $metadata
                ]);
                
                DB::commit();
                return redirect()->route('credit-notes.index')->with('success', 'Credit Note saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Credit Note',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'reference_id' => $salesVoucher->id,
                    'reference_type' => Voucher::class,
                    'draft_data' => null,
                    'metadata' => $metadata
                ];

                // Update Quantities and Physical Stock
                foreach ($lines as $line) {
                    $si = SaleItem::find($line['sale_item_id']);
                    $si->returned_qty += $line['return_qty'];
                    $si->save();
                    
                    $product = Product::lockForUpdate()->find($line['product_id']);
                    $product->available_stock += $line['return_qty'];
                    $product->save();

                    // Inventory Ledger IN entry
                    InventoryLedger::create([
                        'product_id' => $product->id,
                        'type' => 'IN',
                        'quantity' => $line['return_qty'],
                        'balance_after' => $product->available_stock,
                        'reference_type' => 'Credit Note', 
                        'notes' => 'Sales Return via Credit Note'
                    ]);
                }

                // Accounting Logic (Credit Customer, Debit Sales, Debit GST)
                $customerId = $invoice->sale->customer_id;
                $customerLedger = \App\Models\Ledger::where('type', 'customer')->where('reference_id', $customerId)->first();
                if (!$customerLedger) {
                    throw new Exception("Customer Ledger not found.");
                }
                
                $salesLedger = \App\Models\Ledger::where('name', 'Sales A/c')->first();

                $cgstLedger = \App\Models\Ledger::where('name', 'CGST A/c')->first();
                $sgstLedger = \App\Models\Ledger::where('name', 'SGST A/c')->first();
                $igstLedger = \App\Models\Ledger::where('name', 'IGST A/c')->first();

                // Customer gets credited (receivable reduced)
                $entries = [
                    [
                        'ledger_id' => $customerLedger->id,
                        'type' => 'Cr',
                        'amount' => $grandTotal,
                        'narration' => 'Credit Note / Sales Return against ' . $salesVoucher->voucher_number
                    ],
                    [
                        'ledger_id' => $salesLedger->id,
                        'type' => 'Dr',
                        'amount' => $totalTaxable,
                        'narration' => 'Sales Return'
                    ]
                ];
                
                if ($totalCgst > 0 && $cgstLedger) {
                    $entries[] = ['ledger_id' => $cgstLedger->id, 'type' => 'Dr', 'amount' => $totalCgst, 'narration' => 'Output CGST Reversal'];
                }
                if ($totalSgst > 0 && $sgstLedger) {
                    $entries[] = ['ledger_id' => $sgstLedger->id, 'type' => 'Dr', 'amount' => $totalSgst, 'narration' => 'Output SGST Reversal'];
                }
                if ($totalIgst > 0 && $igstLedger) {
                    $entries[] = ['ledger_id' => $igstLedger->id, 'type' => 'Dr', 'amount' => $totalIgst, 'narration' => 'Output IGST Reversal'];
                }

                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                // Update InventoryLedger records with new Voucher ID
                InventoryLedger::where('reference_type', 'Credit Note')->whereNull('reference_id')
                               ->update(['reference_type' => Voucher::class, 'reference_id' => $voucher->id]);
                
                DB::commit();
                return redirect()->route('credit-notes.index')->with('success', 'Credit Note posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Credit Note')->with(['entries.ledger', 'financialYear', 'reference'])->findOrFail($id);
        return view('admin.credit-notes.show', compact('voucher'));
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Credit Note')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('credit-notes.index')->with('success', 'Draft Credit Note deleted.');
            } else {
                throw new Exception("Cancellation of posted Credit Notes requires a comprehensive reversal mechanism to correctly restore physical stock and customer receivable, which is currently not fully implemented.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function generatePdf($id, Request $request)
    {
        $voucher = Voucher::where('type', 'Credit Note')->with(['entries.ledger', 'financialYear', 'reference'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.credit-notes.pdf', compact('voucher'));
        $safeVch = str_replace(['/', '\\', ' '], ['-', '-', '_'], $voucher->voucher_number ?? (string)$voucher->id);
        return $pdf->download('Credit_Note_' . $safeVch . '.pdf');
    }
}
