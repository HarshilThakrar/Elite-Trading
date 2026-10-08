<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'quantity',
        'unit_price', 'total_price',
        'list_price', 'purchase_discount', 'customer_discount',
        'purchase_rate', 'customer_rate', 'profit_amount',
        'profit_percentage', 'line_total',
        'gst_rate', 'gst_amount', 'line_total_with_gst'
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
