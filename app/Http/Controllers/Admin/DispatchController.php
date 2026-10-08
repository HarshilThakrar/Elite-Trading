<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SalesDispatch;
use App\Models\SalesDispatchItem;
use App\Models\InventoryLedger;

class DispatchController extends Controller
{
    public function create($saleId)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($saleId);

        if ($sale->status !== 'Approved') {
            return redirect()->route('sales.show', $sale->id)->with('error', 'Only Approved Sales Orders can be dispatched.');
        }

        return view('admin.sales.dispatch.create', compact('sale'));
    }

    public function store(Request $request, $saleId)
    {
        $request->validate([
            'dispatch_date' => 'required|date',
            'items' => 'required|array',
            'items.*.dispatched_qty' => 'nullable|numeric|min:0',
        ]);

        $sale = Sale::with(['items.product'])->findOrFail($saleId);

        $hasDispatch = false;
        foreach ($request->items as $itemData) {
            if (isset($itemData['dispatched_qty']) && $itemData['dispatched_qty'] > 0) {
                $hasDispatch = true;
                break;
            }
        }

        if (!$hasDispatch) {
            return redirect()->back()->with('error', 'Please enter dispatched quantity for at least one item.');
        }

        try {
            $invoiceId = null;
            $dispatchId = null;
            \DB::transaction(function () use ($request, $sale, &$dispatchId) {
                $dispatchNumber = 'DN-' . date('Ymd') . '-' . strtoupper(\Str::random(4));

                $dispatch = SalesDispatch::create([
                'dispatch_number' => $dispatchNumber,
                'sale_id' => $sale->id,
                'dispatch_date' => $request->dispatch_date,
                'status' => 'Dispatched',
                'notes' => $request->notes,
            ]);

            $isComplete = true;
            $totalCogs = 0;

            foreach ($request->items as $index => $itemData) {
                if (isset($itemData['dispatched_qty']) && $itemData['dispatched_qty'] > 0) {
                    $saleItem = $sale->items[$index];
                    $dispatchedQty = $itemData['dispatched_qty'];
                    
                    // NEGATIVE STOCK PREVENTION
                    if ($saleItem->product) {
                        $physicalStock = $saleItem->product->available_stock + $saleItem->product->reserved_stock;
                        if ($physicalStock < $dispatchedQty) {
                            throw new \Exception("Insufficient stock for product: " . $saleItem->product->item_name . ". Only {$physicalStock} available.");
                        }
                    }

                    $saleItem->dispatched_qty += $dispatchedQty;
                    $saleItem->save();

                    if ($saleItem->dispatched_qty < $saleItem->quantity) {
                        $isComplete = false;
                    }

                    SalesDispatchItem::create([
                        'sales_dispatch_id' => $dispatch->id,
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'dispatched_qty' => $dispatchedQty,
                        'batch_no' => $itemData['batch_no'] ?? null,
                    ]);

                    if ($saleItem->product) {
                        $cogsRate = $saleItem->product->average_purchase_rate > 0 ? $saleItem->product->average_purchase_rate : ($saleItem->unit_price * 0.7); // fallback to 70% of selling price if no purchase history
                        $totalCogs += ($dispatchedQty * $cogsRate);
                    }

                    // Update Inventory
                    $product = $saleItem->product;
                    if ($product) {
                        $product->reserved_stock -= $dispatchedQty;
                        $product->save();

                        $physicalStock = $product->available_stock + $product->reserved_stock;

                        // Log to Inventory Ledger
                        InventoryLedger::create([
                            'product_id' => $product->id,
                            'type' => 'OUT',
                            'quantity' => $dispatchedQty,
                            'reference_type' => SalesDispatch::class,
                            'reference_id' => $dispatch->id,
                            'balance_after' => $physicalStock,
                            'notes' => 'Dispatched via DN ' . $dispatch->dispatch_number,
                        ]);
                    }
                } else {
                    $saleItem = $sale->items[$index];
                    if ($saleItem->dispatched_qty < $saleItem->quantity) {
                        $isComplete = false;
                    }
                }
            }

            // --- ACCOUNTING INTEGRATION ---
            $accountingEngine = app(\App\Services\AccountingEngine::class);
            $finYearService = app(\App\Services\FinancialYearService::class);
            $finYear = $finYearService->validatePostingDate($request->dispatch_date);
            // 2. COGS Voucher (Debit COGS, Credit Inventory)
            $inventoryGroup = \App\Models\AccountGroup::where('name', 'Current Assets')->first();
            $inventoryLedger = \App\Models\Ledger::firstOrCreate(
                ['name' => 'Stock in Hand'],
                ['account_group_id' => $inventoryGroup->id, 'is_system' => true]
            );
            $cogsGroup = \App\Models\AccountGroup::where('name', 'Direct Expenses')->first();
            $cogsLedger = \App\Models\Ledger::firstOrCreate(
                ['name' => 'Cost of Goods Sold (COGS)'],
                ['account_group_id' => $cogsGroup->id, 'is_system' => true]
            );

            if ($finYear && $totalCogs > 0) {
                $accountingEngine->postVoucher([
                    'date' => $request->dispatch_date,
                    'type' => 'Journal',
                    'narration' => 'COGS for Dispatch ' . $dispatchNumber,
                    'financial_year_id' => $finYear->id,
                    'reference_id' => $dispatch->id,
                    'reference_type' => SalesDispatch::class,
                    'status' => 'Posted'
                ], [
                    ['ledger_id' => $cogsLedger->id, 'type' => 'Dr', 'amount' => $totalCogs, 'narration' => 'Cost of Goods Sold'],
                    ['ledger_id' => $inventoryLedger->id, 'type' => 'Cr', 'amount' => $totalCogs, 'narration' => 'Stock Out']
                ]);
            }
            // ------------------------------

            if ($isComplete) {
                $sale->status = 'Delivered';
                $sale->save();
            }
            
            $dispatchId = $dispatch->id;
        });
        
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('dispatch.show', $dispatchId ?? 0)->with('success', 'Dispatch generated successfully. COGS Accounting posted.');
    }

    public function show($id)
    {
        $dispatch = SalesDispatch::with(['sale.customer', 'items.product'])->findOrFail($id);
        return view('admin.sales.dispatch.show', compact('dispatch'));
    }

    public function printSlip($id)
    {
        $dispatch = SalesDispatch::with(['sale.customer', 'items.product'])->findOrFail($id);
        return view('admin.sales.dispatch.print-slip', compact('dispatch'));
    }

    public function generatePdf($id, Request $request)
    {
        $dispatch = SalesDispatch::with(['sale.customer', 'items.product'])->findOrFail($id);
        $sale = $dispatch->sale;
        $is_einvoice = $request->input('type') == 'einvoice';
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sales.dispatch.pdf', compact('dispatch', 'sale', 'is_einvoice'));
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
            
            $dispatchTpl = $fpdi->importPage($pageNo);
            $fpdi->useTemplate($dispatchTpl, 0, 0, 210);
        }

        $finalPdfOutput = $fpdi->Output('S');
        
        return response($finalPdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Delivery_Note_' . $dispatch->dispatch_number . '.pdf"');
    }
}
