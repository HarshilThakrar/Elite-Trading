<?php

namespace App\Services;

use App\Models\AccountingSetting;
use Illuminate\Support\Facades\Cache;

class AccountingSettingsService
{
    /**
     * Get the single settings instance.
     */
    public function getSettings(): AccountingSetting
    {
        return Cache::rememberForever('accounting_settings', function () {
            return AccountingSetting::first() ?? AccountingSetting::create($this->getDefaultNumbering());
        });
    }

    /**
     * Clear the settings cache.
     */
    public function clearCache()
    {
        Cache::forget('accounting_settings');
    }

    /**
     * Get default numbering scheme if table is empty.
     */
    private function getDefaultNumbering()
    {
        return [
            'voucher_numbering' => [
                'Sales' => ['prefix' => 'SV', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Purchase' => ['prefix' => 'PV', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Payment' => ['prefix' => 'PAY', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Receipt' => ['prefix' => 'RV', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Contra' => ['prefix' => 'CON', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Journal' => ['prefix' => 'JV', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Debit Note' => ['prefix' => 'DN', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
                'Credit Note' => ['prefix' => 'CN', 'start' => 1, 'padding' => 5, 'fy_wise' => true, 'mode' => 'automatic'],
            ]
        ];
    }

    public function getSetting($key)
    {
        $settings = $this->getSettings();
        return $settings->$key;
    }

    public function getVoucherNumbering($voucherType)
    {
        $settings = $this->getSettings();
        $numbering = $settings->voucher_numbering ?? [];
        return $numbering[$voucherType] ?? null;
    }

    public function isBackdatedAllowed(): bool
    {
        return (bool) $this->getSettings()->allow_backdated;
    }

    public function isFutureDatedAllowed(): bool
    {
        return (bool) $this->getSettings()->allow_future_dated;
    }

    public function isNarrationRequired(): bool
    {
        return (bool) $this->getSettings()->mandatory_narration;
    }

    public function getDecimalPlaces(string $type = 'amount'): int
    {
        $settings = $this->getSettings();
        return match ($type) {
            'qty' => $settings->qty_decimals,
            'rate' => $settings->rate_decimals,
            default => $settings->amount_decimals,
        };
    }

    public function getRoundingMethod(): string
    {
        return $this->getSettings()->rounding_method;
    }
}
