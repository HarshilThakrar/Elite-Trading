<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class SalesReturn extends Model {
    use Auditable;
    protected $guarded = [];
    public function items() { return $this->hasMany(SalesReturnItem::class); }
    public function sale() { return $this->belongsTo(Sale::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}