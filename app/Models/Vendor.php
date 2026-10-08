<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    protected $fillable = [
        'vendor_code',
        'company_name',
        'contact_person',
        'mobile',
        'email',
        'gst_no',
        'lead_time_days',
        'status',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function ledger()
    {
        return $this->hasOne(Ledger::class, 'reference_id')->where('type', 'vendor');
    }
}
