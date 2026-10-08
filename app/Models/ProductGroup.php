<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    protected $fillable = ['name'];

    public function subgroups()
    {
        return $this->hasMany(ProductSubgroup::class);
    }
}
