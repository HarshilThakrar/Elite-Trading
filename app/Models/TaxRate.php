<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'rate',
        'cgst',
        'sgst',
        'igst',
        'is_active'
    ];
}