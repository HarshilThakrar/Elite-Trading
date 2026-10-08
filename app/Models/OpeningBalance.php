<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    use \App\Traits\Auditable;
    
    protected $fillable = [
        'financial_year_id',
        'ledger_id',
        'amount',
        'type',
        'opening_date',
        'narration',
        'reference',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'opening_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
