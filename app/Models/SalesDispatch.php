<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDispatch extends Model
{
    use HasFactory;

    protected $fillable = ['dispatch_number', 'sale_id', 'dispatch_date', 'status', 'notes'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(SalesDispatchItem::class);
    }
}
