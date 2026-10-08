<?php

namespace App\Services;

use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitabilityService
{
    /**
     * Get profitability (Gross Margin) by Product.
     */
    public function getProfitabilityByProduct(int $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        // Revenue = SUM(total_price)
        // COGS (Cost of Goods Sold) = SUM(quantity * lp_price) (assuming lp_price is average cost for this example, ideally use actual purchase cost)
        // Profit = Revenue - COGS
        
        return SaleItem::select('product_id', 
                DB::raw('SUM(total_price) as total_revenue'),
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->whereHas('sale', function ($query) use ($startDate) {
                $query->where('sale_date', '>=', $startDate)
                      ->where('status', 'Completed');
            })
            ->with('product')
            ->groupBy('product_id')
            ->get()
            ->map(function ($item) {
                $cogs = $item->total_quantity * $item->product->lp_price;
                $item->cogs = $cogs;
                $item->gross_profit = $item->total_revenue - $cogs;
                $item->margin_percentage = $item->total_revenue > 0 ? ($item->gross_profit / $item->total_revenue) * 100 : 0;
                return $item;
            })
            ->sortByDesc('gross_profit');
    }

    /**
     * Get profitability (Gross Margin) by Customer.
     */
    public function getProfitabilityByCustomer(int $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        $sales = SaleItem::select('product_id', 'sale_id', 'quantity', 'total_price')
            ->whereHas('sale', function ($query) use ($startDate) {
                $query->where('sale_date', '>=', $startDate)
                      ->where('status', 'Completed');
            })
            ->with(['product', 'sale.customer'])
            ->get();

        $customerProfitability = [];

        foreach ($sales as $item) {
            $customerId = $item->sale->customer_id;
            $customerName = $item->sale->customer->company_name;

            if (!isset($customerProfitability[$customerId])) {
                $customerProfitability[$customerId] = [
                    'customer_id' => $customerId,
                    'company_name' => $customerName,
                    'total_revenue' => 0,
                    'total_cogs' => 0,
                ];
            }

            $cogs = $item->quantity * $item->product->lp_price;

            $customerProfitability[$customerId]['total_revenue'] += $item->total_price;
            $customerProfitability[$customerId]['total_cogs'] += $cogs;
        }

        foreach ($customerProfitability as &$data) {
            $data['gross_profit'] = $data['total_revenue'] - $data['total_cogs'];
            $data['margin_percentage'] = $data['total_revenue'] > 0 
                ? ($data['gross_profit'] / $data['total_revenue']) * 100 
                : 0;
        }

        return collect($customerProfitability)->sortByDesc('gross_profit');
    }
}
