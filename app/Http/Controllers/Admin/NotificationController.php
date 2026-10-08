<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        // Low Stock Products
        $lowStockProducts = \App\Models\Product::whereColumn('available_stock', '<=', 'reorder_level')->get();
            
        // Pending Sales (Approved but not Dispatched)
        $pendingSales = \App\Models\Sale::with('customer')
            ->where('status', 'Approved')
            ->orderBy('sale_date', 'asc')
            ->get();
            
        // Pending Purchases (Approved but not Received)
        $pendingPurchases = \App\Models\Purchase::with('vendor')
            ->where('status', 'Approved')
            ->orderBy('po_date', 'asc')
            ->get();

        return view('admin.notifications.index', compact('lowStockProducts', 'pendingSales', 'pendingPurchases'));
    }
}
