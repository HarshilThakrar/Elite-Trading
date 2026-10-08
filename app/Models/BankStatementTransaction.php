<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Ledger;
use App\Models\JournalEntry;

class BankStatementTransaction extends Model
{
    protected $fillable = [
        'bank_ledger_id',
        'transaction_date',
        'value_date',
        'description',
        'reference_number',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'reconciliation_status',
        'matched_journal_entry_id',
        'matched_at',
        'matched_by',
        'import_hash',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'matched_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function bankLedger()
    {
        return $this->belongsTo(Ledger::class, 'bank_ledger_id');
    }

    public function matchedJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'matched_journal_entry_id');
    }

    public function matchedBy()
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    // A helper to get the "absolute amount" for matching
    // (Assuming bank debits and credits are just amounts, we can use the non-zero one)
    public function getAmountAttribute()
    {
        if ($this->debit_amount > 0) return $this->debit_amount;
        if ($this->credit_amount > 0) return $this->credit_amount;
        return 0;
    }
}
