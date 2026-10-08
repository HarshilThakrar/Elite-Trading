<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Purchase;

class FinancialController extends Controller
{
    public function index()
    {
        // Total Revenue (Approved/Dispatched sales)
        $totalRevenue = Sale::whereIn('status', ['Approved', 'Dispatched'])->sum('total_amount');
        
        // Total Expenses (Approved/Completed purchases)
        $totalExpenses = Purchase::whereIn('status', ['Approved', 'Completed', 'Received'])->sum('total_amount');
        
        // Gross Profit
        $grossProfit = $totalRevenue - $totalExpenses;
        
        // Recent Transactions
        $recentSales = Sale::with('customer')->orderBy('created_at', 'desc')->take(5)->get();
        $recentPurchases = Purchase::with('vendor')->orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.financial.index', compact(
            'totalRevenue', 'totalExpenses', 'grossProfit', 'recentSales', 'recentPurchases'
        ));
    }
}
