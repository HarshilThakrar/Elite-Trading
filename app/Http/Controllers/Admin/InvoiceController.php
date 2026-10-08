<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function selectSale()
    {
        $approvedSalesOrders = \App\Models\Sale::with('customer')->where('status', 'Approved')->get();
        return view('admin.invoices.select-sale', compact('approvedSalesOrders'));
    }

    public function create(Sale $sale)
    {
        return view('admin.invoices.create', compact('sale'));
    }

    public function store(Request $request, Sale $sale)
    {
        $request->validate([
            'invoice_date' => 'required|date',
            'items.*.invoiced_qty' => 'nullable|numeric|min:0',
        ]);

        $hasInvoice = false;
        $totalAmount = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $invoiceTotal = 0;

        foreach ($request->items as $itemData) {
            if (isset($itemData['invoiced_qty']) && $itemData['invoiced_qty'] > 0) {
                $hasInvoice = true;
                break;
            }
        }

        if (!$hasInvoice) {
            return redirect()->back()->with('error', 'Please enter invoiced quantity for at least one item.');
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $gstEngine = app(\App\Services\GstEngine::class);
        $isInterState = false; // Hardcoded for demo as per plan

        \DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'sale_id' => $sale->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $request->invoice_date,
                'total_amount' => 0,
                'status' => 'Posted',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $index => $itemData) {
                if (isset($itemData['invoiced_qty']) && $itemData['invoiced_qty'] > 0) {
                    $invoicedQty = $itemData['invoiced_qty'];
                    $saleItem = SaleItem::findOrFail($index);
                    
                    $availableQty = $saleItem->dispatched_qty - $saleItem->invoiced_qty;
                    if ($invoicedQty > $availableQty) {
                        throw new \Exception("Cannot invoice more than available dispatched quantity for product: " . ($saleItem->product->item_name ?? 'Unknown'));
                    }
                    
                    $saleItem->invoiced_qty += $invoicedQty;
                    $saleItem->save();

                    $itemAmount = $invoicedQty * $saleItem->unit_price;
                    $totalAmount += $itemAmount;
                    $invoiceTotal += $itemAmount;

                    if ($saleItem->product && $saleItem->product->gst_rate > 0) {
                        $gstCalc = $gstEngine->calculate($itemAmount, $saleItem->product->gst_rate, $isInterState);
                        $totalCgst += $gstCalc['cgst'];
                        $totalSgst += $gstCalc['sgst'];
                        $totalIgst += $gstCalc['igst'];
                        $invoiceTotal += $gstCalc['total_tax'];
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $saleItem->product_id,
                        'quantity' => $invoicedQty,
                        'unit_price' => $saleItem->unit_price,
                        'total_price' => $itemAmount,
                    ]);
                }
            }

            $invoice->update(['total_amount' => $invoiceTotal]);
            
            // --- ACCOUNTING INTEGRATION ---
            $accountingEngine = app(\App\Services\AccountingEngine::class);
            
            $salesLedger = \App\Models\Ledger::where('name', 'Sales A/c')->first();
            $customerLedger = \App\Models\Ledger::where('type', 'customer')->where('reference_id', $sale->customer_id)->first();
            $finYearService = app(\App\Services\FinancialYearService::class);
            $finYear = $finYearService->validatePostingDate($request->invoice_date);

            $cgstLedger = \App\Models\Ledger::where('name', 'CGST A/c')->first();
            $sgstLedger = \App\Models\Ledger::where('name', 'SGST A/c')->first();
            $igstLedger = \App\Models\Ledger::where('name', 'IGST A/c')->first();

            if ($salesLedger && $customerLedger && $finYear && $invoiceTotal > 0) {
                $voucherData = [
                    'date' => $request->invoice_date,
                    'type' => 'Sales',
                    'narration' => 'Sales Invoice ' . $invoiceNumber,
                    'financial_year_id' => $finYear->id,
                    'reference_id' => $invoice->id,
                    'reference_type' => \App\Models\Invoice::class,
                    'status' => 'Posted'
                ];

                $entries = [
                    [
                        'ledger_id' => $customerLedger->id,
                        'type' => 'Dr',
                        'amount' => $invoiceTotal,
                        'narration' => 'Invoice Amount (including GST)'
                    ],
                    [
                        'ledger_id' => $salesLedger->id,
                        'type' => 'Cr',
                        'amount' => $totalAmount,
                        'narration' => 'Sales Amount'
                    ]
                ];

                if ($totalCgst > 0 && $cgstLedger) {
                    $entries[] = ['ledger_id' => $cgstLedger->id, 'type' => 'Cr', 'amount' => $totalCgst, 'narration' => 'CGST on Sales'];
                }
                if ($totalSgst > 0 && $sgstLedger) {
                    $entries[] = ['ledger_id' => $sgstLedger->id, 'type' => 'Cr', 'amount' => $totalSgst, 'narration' => 'SGST on Sales'];
                }
                if ($totalIgst > 0 && $igstLedger) {
                    $entries[] = ['ledger_id' => $igstLedger->id, 'type' => 'Cr', 'amount' => $totalIgst, 'narration' => 'IGST on Sales'];
                }

                $accountingEngine->postVoucher($voucherData, $entries);
            }
            // ------------------------------
            
            \DB::commit();
            
            return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice created and Sales Voucher posted successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['sale.customer', 'items.product']);
        $sale = $invoice->sale;
        return view('admin.invoices.show', compact('invoice', 'sale'));
    }

    public function generatePdf(Invoice $invoice, Request $request)
    {
        $invoice->load(['sale.customer', 'items.product']);
        $sale = $invoice->sale;
        
        $is_einvoice = $request->get('type') == 'einvoice';
        
        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice', 'sale', 'is_einvoice'));
        $pdf->setPaper('A4', 'portrait');
        $dompdfOutput = $pdf->output();

        $fpdi = new \setasign\Fpdi\Fpdi();
        
        $letterheadPath = base_path('Elite LH File 0326.pdf');
        
        if (file_exists($letterheadPath)) {
            $fpdi->setSourceFile($letterheadPath);
            $letterheadTpl = $fpdi->importPage(1);
        } else {
            $letterheadTpl = null;
        }

        $pageCount = $fpdi->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($dompdfOutput));

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $fpdi->AddPage();
            
            if ($letterheadTpl) {
                $fpdi->useTemplate($letterheadTpl, 0, 0, 210);
            }
            
            $invoiceTpl = $fpdi->importPage($pageNo);
            $fpdi->useTemplate($invoiceTpl, 0, 0, 210);
        }

        $safeNum = str_replace(['/', '\\', ' '], ['-', '-', '_'], $invoice->invoice_number ?? (string)$invoice->id);
        
        return response($finalPdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Invoice_' . $safeNum . '.pdf"');
    }
}
