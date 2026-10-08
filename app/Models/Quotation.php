<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number', 'customer_id', 'quotation_date', 'valid_until', 
        'subtotal', 'freight_charges', 'grand_total', 'total_amount',
        'gst_amount', 'grand_total_with_gst',
        'status', 'customer_status', 'notes', 'terms_and_conditions', 'created_by'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // Average profit percentage
    public function getAverageProfitPercentageAttribute()
    {
        if ($this->items->isEmpty()) {
            return 0;
        }
        return $this->items->avg('profit_percentage');
    }
}
