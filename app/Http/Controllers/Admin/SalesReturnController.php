<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SalesReturn, SalesReturnItem, Sale, Invoice, Ledger, Product};
use App\Services\AccountingEngine;
use DB;
class SalesReturnController extends Controller
{
    public function index()
    {
        $returns = SalesReturn::with('sale.customer')->latest()->paginate(20);
        return view('admin.sales_returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $saleId = $request->sale_id;
        $sale = Sale::with('items.product', 'customer', 'invoice')->findOrFail($saleId);
        return view('admin.sales_returns.create', compact('sale'));
    }

    public function store(Request $request, AccountingEngine $accountingEngine)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|array',
        ]);

        try {
            DB::beginTransaction();
            $sale = Sale::with('items', 'invoice', 'customer')->findOrFail($request->sale_id);
            $returnNumber = 'SR-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $salesReturn = SalesReturn::create([
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'invoice_id' => $sale->invoice ? $sale->invoice->id : null,
                'return_date' => $request->return_date,
                'notes' => $request->notes,
                'status' => 'Completed',
                'total_amount' => 0
            ]);

            $totalReturnAmount = 0;
            $totalCogsReturn = 0;

            foreach ($request->items as $index => $itemData) {
                if ($itemData['qty'] > 0) {
                    $saleItem = $sale->items->where('product_id', $itemData['product_id'])->first();
                    if (!$saleItem || $saleItem->dispatched_qty < $itemData['qty']) {
                        throw new \Exception("Cannot return more than dispatched quantity.");
                    }
                    
                    $salesReturn->items()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['qty'],
                        'unit_price' => $saleItem->unit_price
                    ]);
                    
                    $lineTotal = $itemData['qty'] * $saleItem->unit_price;
                    $totalReturnAmount += $lineTotal;
                    
                    // Inventory Reversal
                    $product = Product::find($itemData['product_id']);
                    $product->available_stock += $itemData['qty'];
                    $product->save();
                    
                    $totalCogsReturn += ($itemData['qty'] * $product->purchase_rate);
                }
            }
            
            $salesReturn->update(['total_amount' => $totalReturnAmount]);

            // Accounting Reversal (Credit Note)
            $salesReturnLedger = Ledger::firstOrCreate(['name' => 'Sales Return A/c'], ['type' => 'income', 'nature' => 'Dr']);
            $customerLedger = Ledger::where('type', 'customer')->where('reference_id', $sale->customer_id)->first();
            
            $entries = [
                ['ledger_id' => $salesReturnLedger->id, 'type' => 'Dr', 'amount' => $totalReturnAmount, 'narration' => "Sales Return $returnNumber"],
                ['ledger_id' => $customerLedger->id, 'type' => 'Cr', 'amount' => $totalReturnAmount, 'narration' => "Credit Note for SR $returnNumber"]
            ];
            
            $accountingEngine->postVoucher([
                'type' => 'Credit Note',
                'date' => $request->return_date,
                'narration' => "Sales Return against Sale " . $sale->invoice_number,
                'reference_type' => 'SalesReturn',
                'reference_id' => $salesReturn->id
            ], $entries);

            // COGS Reversal
            $stockLedger = Ledger::where('name', 'Stock in Hand')->first();
            $cogsLedger = Ledger::where('name', 'Cost of Goods Sold (COGS)')->first();
            if ($stockLedger && $cogsLedger && $totalCogsReturn > 0) {
                $accountingEngine->postVoucher([
                    'type' => 'Journal',
                    'date' => $request->return_date,
                    'narration' => "Inventory adjustment for Sales Return $returnNumber",
                    'reference_type' => 'SalesReturn_COGS',
                    'reference_id' => $salesReturn->id
                ], [
                    ['ledger_id' => $stockLedger->id, 'type' => 'Dr', 'amount' => $totalCogsReturn],
                    ['ledger_id' => $cogsLedger->id, 'type' => 'Cr', 'amount' => $totalCogsReturn]
                ]);
            }

            DB::commit();
            return redirect()->route('sales-returns.index')->with('success', 'Sales Return created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}