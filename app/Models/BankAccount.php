<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'branch',
        'opening_balance',
        'is_active'
    ];

    public function ledger()
    {
        return $this->hasOne(Ledger::class, 'reference_id')->where('type', 'bank_account');
    }
}