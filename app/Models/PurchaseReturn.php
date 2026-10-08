<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PurchaseReturn extends Model {
    use Auditable;
    protected $guarded = [];
    public function items() { return $this->hasMany(PurchaseReturnItem::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
}