<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePlan extends Model
{
    protected $fillable = [
        'plan_date', 'status', 'notes'
    ];

    public function items()
    {
        return $this->hasMany(PurchasePlanItem::class);
    }
}
