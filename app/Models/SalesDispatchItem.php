<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDispatchItem extends Model
{
    use HasFactory;

    protected $fillable = ['sales_dispatch_id', 'sale_item_id', 'product_id', 'dispatched_qty', 'batch_no'];

    public function dispatch()
    {
        return $this->belongsTo(SalesDispatch::class, 'sales_dispatch_id');
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
