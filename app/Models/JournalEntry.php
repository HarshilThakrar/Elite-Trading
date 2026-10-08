<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = [
        'voucher_id',
        'ledger_id',
        'type',
        'amount',
        'narration',
        'cost_centre_id'
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function costCentre()
    {
        return $this->belongsTo(CostCentre::class);
    }
}