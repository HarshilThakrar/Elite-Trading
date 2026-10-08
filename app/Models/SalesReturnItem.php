<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesReturnItem extends Model {
    protected $guarded = [];
    public function salesReturn() { return $this->belongsTo(SalesReturn::class); }
    public function product() { return $this->belongsTo(Product::class); }
}