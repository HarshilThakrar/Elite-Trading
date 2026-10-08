<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    protected $fillable = [
        'name',
        'category',
        'is_active'
    ];
}