<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSubgroup extends Model
{
    protected $fillable = ['product_group_id', 'name'];

    public function group()
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }
}
