<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'company_name',
        'contact_person',
        'mobile',
        'email',
        'gst_no',
        'address',
        'city',
        'state',
        'pincode',
        'credit_limit',
        'payment_terms',
        'status',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function ledger()
    {
        return $this->hasOne(Ledger::class, 'reference_id')->where('type', 'customer');
    }
}
