<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'currency_symbol',
        'mandatory_narration',
        'amount_decimals',
        'qty_decimals',
        'rate_decimals',
        'rounding_method',
        'allow_backdated',
        'max_backdate_days',
        'allow_future_dated',
        'max_future_days',
        'allow_voucher_editing',
        'allow_voucher_cancellation',
        'prevent_inactive_ledger_posting',
        'voucher_numbering',
    ];

    protected $casts = [
        'mandatory_narration' => 'boolean',
        'allow_backdated' => 'boolean',
        'allow_future_dated' => 'boolean',
        'allow_voucher_editing' => 'boolean',
        'allow_voucher_cancellation' => 'boolean',
        'prevent_inactive_ledger_posting' => 'boolean',
        'voucher_numbering' => 'json',
    ];
}
