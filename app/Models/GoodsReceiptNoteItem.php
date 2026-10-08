<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptNoteItem extends Model
{
    use HasFactory;

    protected $fillable = ['goods_receipt_note_id', 'purchase_item_id', 'product_id', 'received_qty', 'purchase_rate', 'batch_no'];

    public function grn()
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'goods_receipt_note_id');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
