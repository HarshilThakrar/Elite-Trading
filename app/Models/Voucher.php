<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = [
        'voucher_number',
        'date',
        'type',
        'narration',
        'financial_year_id',
        'reference_id',
        'reference_type',
        'created_by',
        'status',
        'draft_data',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'draft_data' => 'array',
        'metadata' => 'array',
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}