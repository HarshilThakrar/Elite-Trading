<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = [
        'name',
        'ledger_code',
        'account_group_id',
        'opening_balance',
        'opening_balance_type',
        'opening_balance_date',
        'is_system',
        'is_active',
        'type',
        'reference_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'opening_balance_date' => 'date',
    ];

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function openingBalances()
    {
        return $this->hasMany(OpeningBalance::class);
    }

    /**
     * Get the authoritative opening balance for a specific Financial Year.
     */
    public function getOpeningBalanceForYear($financialYearId)
    {
        return $this->openingBalances()->where('financial_year_id', $financialYearId)->first();
    }

    /**
     * Build the full accounting path.
     * Prevents N+1 queries by leveraging AccountGroup static cache.
     */
    public function getPath()
    {
        if ($this->accountGroup) {
            return $this->accountGroup->getPath() . ' &rarr; ' . $this->name;
        }
        return $this->name;
    }
}