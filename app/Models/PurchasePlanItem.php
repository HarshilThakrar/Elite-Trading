<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePlanItem extends Model
{
    protected $fillable = [
        'purchase_plan_id', 'product_id', 'required_quantity', 'suggested_vendor_id'
    ];

    public function purchasePlan()
    {
        return $this->belongsTo(PurchasePlan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function suggestedVendor()
    {
        return $this->belongsTo(Vendor::class, 'suggested_vendor_id');
    }
}
