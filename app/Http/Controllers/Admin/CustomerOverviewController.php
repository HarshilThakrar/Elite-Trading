<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class CustomerOverviewController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        
        // Fetch all products to get their average purchase rate (cost) to avoid N+1 queries inside loop
        // Product's getAveragePurchaseRateAttribute runs a DB query, so we precalculate them here.
        // Wait, getting ALL products might still trigger many queries if not careful, 
        // but it's better than doing it per sale item. Actually, let's just get the attribute.
        // To be even more efficient, we can just use the accessor as it is, since Laravel will run queries.
        // Wait, a better approach to prevent 1000s of queries:
        // Cache product costs in an array
        $products = Product::all();
        $productCosts = [];
        foreach ($products as $product) {
            $productCosts[$product->id] = $product->average_purchase_rate;
        }

        $customers = Customer::with(['sales' => function($q) {
            $q->whereIn('status', ['Delivered', 'Completed', 'Approved', 'Shipped', 'Invoiced']); // Only count successful sales
        }, 'sales.items'])->get()->map(function ($customer) use ($now, $productCosts) {
            
            $monthConsumption = 0;
            $yearlyConsumption = 0;
            $grossProfit = 0;
            $totalRevenue = 0;

            foreach ($customer->sales as $sale) {
                $saleDate = Carbon::parse($sale->sale_date);
                
                if ($saleDate->year === $now->year) {
                    $yearlyConsumption += $sale->total_amount;
                    
                    if ($saleDate->month === $now->month) {
                        $monthConsumption += $sale->total_amount;
                    }
                }
                
                // Calculate gross profit
                foreach ($sale->items as $item) {
                    $cost = $productCosts[$item->product_id] ?? 0;
                    $revenue = $item->total_price;
                    $totalRevenue += $revenue;
                    $grossProfit += ($revenue - ($cost * $item->quantity));
                }
            }
            
            $customer->month_consumption = $monthConsumption;
            $customer->yearly_consumption = $yearlyConsumption;
            $customer->gross_profit = $grossProfit;
            $customer->gross_profit_percentage = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
            
            return $customer;
        });

        return view('admin.customer_overview.index', compact('customers'));
    }
}

