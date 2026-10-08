<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $engine = app(\App\Services\AccountingEngine::class);

        // 1. Total outstanding - payable (Total amount we owe vendors - using true accounting ledgers)
        $payableGroup = \App\Models\AccountGroup::where('name', 'Sundry Creditors')->first();
        $totalPayable = 0;
        if ($payableGroup) {
            $ledgers = \App\Models\Ledger::where('account_group_id', $payableGroup->id)->get();
            foreach ($ledgers as $l) {
                $bal = $engine->getLedgerBalance($l->id);
                if ($bal['type'] == 'Cr') {
                    $totalPayable += $bal['balance'];
                } else {
                    $totalPayable -= $bal['balance'];
                }
            }
        }

        // 2. Total outstanding - receivable (Total amount customers owe us - using true accounting ledgers)
        $receivableGroup = \App\Models\AccountGroup::where('name', 'Sundry Debtors')->first();
        $totalReceivable = 0;
        if ($receivableGroup) {
            $ledgers = \App\Models\Ledger::where('account_group_id', $receivableGroup->id)->get();
            foreach ($ledgers as $l) {
                $bal = $engine->getLedgerBalance($l->id);
                if ($bal['type'] == 'Dr') {
                    $totalReceivable += $bal['balance'];
                } else {
                    $totalReceivable -= $bal['balance'];
                }
            }
        }

        // 3. Total outstanding - sales (Sales for current month)
        $salesForMonth = Sale::with('items.product')
                          ->whereMonth('sale_date', $currentMonth)
                          ->whereYear('sale_date', $currentYear)
                          ->get();
                          
        $totalSalesWithoutGST = 0;
        $totalSalesWithGST = 0;
        
        foreach ($salesForMonth as $sale) {
            $totalSalesWithoutGST += $sale->total_amount;
            
            $gstAmount = 0;
            foreach ($sale->items as $item) {
                $itemTotal = $item->quantity * $item->unit_price;
                $gstRate = $item->product ? $item->product->gst_rate : 18;
                $gstAmount += $itemTotal * ($gstRate / 100);
            }
            $totalSalesWithGST += ($sale->total_amount + $gstAmount);
        }

        // 4. Total outstanding - purchase (Purchases for current month)
        $totalPurchases = \App\Models\Purchase::whereMonth('po_date', $currentMonth)
                                              ->whereYear('po_date', $currentYear)
                                              ->sum('total_amount') ?? 0;

        // 5. Pending sales orders to be invoiced and dispatched
        $pendingSalesOrders = Sale::whereIn('status', ['Draft', 'Approved', 'Pending'])->count();

        // 6. Total stock
        $totalStock = Product::sum('available_stock') ?? 0;

        // 7. Pending purchase order to be received
        $pendingPurchaseOrders = \App\Models\Purchase::whereIn('status', ['Draft', 'Ordered', 'Pending'])->count();

        // 8. Total Invoices Generated
        $totalInvoices = \App\Models\Invoice::count();

        // 9. Recent Purchase Orders
        $recentPurchaseOrders = \App\Models\Purchase::with('vendor')->latest()->take(5)->get();

        // 10. Recent Invoices
        $recentInvoices = \App\Models\Invoice::with('sale.customer')->latest()->take(5)->get();

        // 11. Recent Sales Orders with Items and Customer
        $recentSalesOrders = Sale::with(['customer', 'items.product'])->latest()->take(5)->get();

        // Data for Charts: Last 6 months Sales vs Purchases
        $chartLabels = [];
        $salesData = [];
        $purchasesData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartLabels[] = $month->format('M Y');
            
            $salesData[] = Sale::whereMonth('sale_date', $month->month)
                               ->whereYear('sale_date', $month->year)
                               ->sum('total_amount') ?? 0;
                               
            $purchasesData[] = \App\Models\Purchase::whereMonth('po_date', $month->month)
                                                   ->whereYear('po_date', $month->year)
                                                   ->sum('total_amount') ?? 0;
        }
        
        // Data for 12 Months Sales Chart
        $yearlyLabels = [];
        $yearlySalesData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthDate = Carbon::createFromDate($currentYear, $i, 1);
            $yearlyLabels[] = $monthDate->format('F');
            $yearlySalesData[] = Sale::whereMonth('sale_date', $i)
                               ->whereYear('sale_date', $currentYear)
                               ->sum('total_amount') ?? 0;
        }

        $approvedSalesOrders = Sale::with('customer')->where('status', 'Approved')->get();

        return view('dashboard', compact(
            'totalPayable',
            'totalReceivable',
            'totalSalesWithGST',
            'totalSalesWithoutGST',
            'totalPurchases',
            'pendingSalesOrders',
            'totalStock',
            'pendingPurchaseOrders',
            'chartLabels',
            'salesData',
            'purchasesData',
            'yearlyLabels',
            'yearlySalesData',
            'approvedSalesOrders',
            'totalInvoices',
            'recentPurchaseOrders',
            'recentInvoices',
            'recentSalesOrders'
        ));
    }
}
