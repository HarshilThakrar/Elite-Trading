<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LpHistory extends Model
{
    protected $fillable = [
        'product_id',
        'old_price',
        'new_price',
        'user_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
