<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryAnalyticsService
{
    /**
     * Get Fast Moving Items based on total quantity sold over a period.
     */
    public function getFastMovingItems(int $days = 30, int $limit = 5)
    {
        $startDate = Carbon::now()->subDays($days);

        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('sale', function ($query) use ($startDate) {
                $query->where('sale_date', '>=', $startDate)
                      ->where('status', 'Completed');
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Get Slow Moving Items based on lowest quantity sold over a period.
     */
    public function getSlowMovingItems(int $days = 90, int $limit = 5)
    {
        $startDate = Carbon::now()->subDays($days);

        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('sale', function ($query) use ($startDate) {
                $query->where('sale_date', '>=', $startDate)
                      ->where('status', 'Completed');
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Get Dead Stock (Items with stock > 0 but NO sales in the last X days).
     */
    public function getDeadStock(int $days = 180)
    {
        $startDate = Carbon::now()->subDays($days);

        // Find products that have stock > 0
        $productsWithStock = Product::where('available_stock', '>', 0)->pluck('id');

        // Find products that WERE sold in the last X days
        $recentlySoldProductIds = SaleItem::whereHas('sale', function ($query) use ($startDate) {
            $query->where('sale_date', '>=', $startDate)
                  ->where('status', 'Completed');
        })->pluck('product_id')->unique();

        // Dead stock = Products with stock that are NOT in the recently sold list
        $deadStockProductIds = $productsWithStock->diff($recentlySoldProductIds);

        return Product::whereIn('id', $deadStockProductIds)->get();
    }

    /**
     * Smart Reorder Planning: Calculate items needing reorder
     */
    public function getSmartReorderSuggestions()
    {
        // Products where available_stock <= reorder_level
        return Product::where('available_stock', '>', 0)
        ->get()
        ->filter(function($product) {
            return $product->available_stock <= $product->reorder_level;
        });
    }
}
