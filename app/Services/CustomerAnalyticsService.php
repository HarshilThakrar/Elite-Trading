<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerAnalyticsService
{
    /**
     * Get Customer Wise Consumption (Items purchased by customer over a period).
     */
    public function getCustomerConsumption(int $customerId, int $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_spent'))
            ->whereHas('sale', function ($query) use ($customerId, $startDate) {
                $query->where('customer_id', $customerId)
                      ->where('sale_date', '>=', $startDate)
                      ->whereIn('status', ['Approved', 'Dispatched']);
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->get();
    }

    /**
     * Get Customer Lifetime Value and Order Frequency.
     */
    public function getCustomerLifetimeValue(int $customerId)
    {
        $stats = Sale::where('customer_id', $customerId)
            ->whereIn('status', ['Approved', 'Dispatched'])
            ->select(
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(total_amount) as lifetime_value'),
                DB::raw('MAX(sale_date) as last_order_date')
            )
            ->first();

        return $stats;
    }

    /**
     * Get Top Customers by Revenue over a period.
     */
    public function getTopCustomers($startDate = null, $endDate = null, int $limit = 5)
    {
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30);
        }
        if (!$endDate) {
            $endDate = Carbon::now();
        }

        return Sale::select('customer_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->whereIn('status', ['Approved', 'Dispatched'])
            ->with('customer')
            ->groupBy('customer_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }
}
