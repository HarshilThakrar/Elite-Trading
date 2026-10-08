<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Exception;

class VoucherNumberService
{
    protected $settingsService;
    protected $fyService;

    public function __construct(AccountingSettingsService $settingsService, FinancialYearService $fyService)
    {
        $this->settingsService = $settingsService;
        $this->fyService = $fyService;
    }

    /**
     * Generate the next unique voucher number safely using DB locking.
     */
    public function generateNextNumber(string $voucherType, string $date): string
    {
        $finYear = $this->fyService->resolveByDate($date);
        
        $config = $this->settingsService->getVoucherNumbering($voucherType);
        
        if (!$config) {
            // Fallback default numbering if config is somehow missing
            $config = ['prefix' => strtoupper(substr($voucherType, 0, 2)), 'start' => 1, 'padding' => 4, 'fy_wise' => true];
        }

        return DB::transaction(function () use ($voucherType, $finYear, $config) {
            // Lock the highest voucher for this type and FY to prevent concurrent duplicates
            $query = Voucher::where('type', $voucherType);
            
            if ($config['fy_wise'] ?? true) {
                $query->where('financial_year_id', $finYear->id);
            }
            
            // Apply write lock
            $lastVoucher = $query->orderBy('id', 'desc')->lockForUpdate()->first();
            
            $startNum = (int)($config['start'] ?? 1);
            $nextNum = $startNum;

            if ($lastVoucher) {
                // Extract number using regex, assuming format prefix/.../0001
                if (preg_match('/(\d+)$/', $lastVoucher->voucher_number, $matches)) {
                    $nextNum = (int)$matches[1] + 1;
                } else {
                    $nextNum = $startNum;
                }
            }
            
            // Construct the final number
            $padding = (int)($config['padding'] ?? 4);
            $paddedNum = str_pad((string)$nextNum, $padding, '0', STR_PAD_LEFT);
            $prefix = $config['prefix'] ?? '';
            
            $components = array_filter([
                $prefix,
                ($config['fy_wise'] ?? true) ? $finYear->name : null,
                $paddedNum
            ]);

            return implode('/', $components);
        });
    }
}
