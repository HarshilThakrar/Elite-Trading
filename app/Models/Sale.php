<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use \App\Traits\Auditable;

    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'customer_id', 'sale_date', 'total_amount', 'status', 'notes', 
        'eway_bill_no', 'eway_bill_date',
        'delivery_note', 'mode_of_payment', 'reference_no_date', 'other_references',
        'buyer_order_no', 'buyer_order_date', 'dispatched_through', 'destination', 'terms_of_delivery'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
