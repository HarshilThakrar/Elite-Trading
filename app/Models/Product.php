<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'part_code', 'product_group_id', 'product_subgroup_id', 'hsn_code', 'gst_rate', 'item_name', 'description',
        'unit', 'lp_price', 'minimum_stock', 'reorder_level', 'maximum_stock', 'status', 'available_stock', 'reserved_stock', 'lead_time_days', 'abc_category'
    ];

    public function group()
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    public function subgroup()
    {
        return $this->belongsTo(ProductSubgroup::class, 'product_subgroup_id');
    }

    public function getAveragePurchaseRateAttribute()
    {
        $averageRate = \App\Models\PurchaseItem::where('product_id', $this->id)
            ->whereHas('purchase', function($query) {
                $query->whereIn('status', ['Approved', 'Received']);
            })
            ->avg('unit_price');
        
        return $averageRate ? round((float)$averageRate, 2) : 0;
    }

    public function getAveragePurchaseDiscountAttribute()
    {
        $lp = $this->lp_price;
        $avgRate = $this->average_purchase_rate;
        
        if ($lp > 0 && $avgRate > 0 && $lp > $avgRate) {
            return round((($lp - $avgRate) / $lp) * 100, 2);
        }
        
        return 0;
    }

    public function lpHistories()
    {
        return $this->hasMany(LpHistory::class)->orderBy('created_at', 'desc');
    }
}
