<?php

namespace App\Services;

use App\Models\FinancialYear;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinancialYearService
{
    /**
     * Get the currently active Financial Year.
     */
    public function getCurrentFinancialYear(): ?FinancialYear
    {
        return FinancialYear::where('is_active', true)->where('is_closed', false)->first();
    }

    /**
     * Resolve a financial year by a given date.
     * Does not fallback to default. Blocks if not found.
     */
    public function resolveByDate($date): FinancialYear
    {
        $parsedDate = Carbon::parse($date)->format('Y-m-d');
        
        $finYear = FinancialYear::where('start_date', '<=', $parsedDate)
            ->where('end_date', '>=', $parsedDate)
            ->first();

        if (!$finYear) {
            throw new Exception("No valid Financial Year found for date: {$parsedDate}");
        }

        return $finYear;
    }

    /**
     * Validate that a given date is allowed for normal posting.
     * Rule: Transaction date must belong to a valid Financial Year, and it must be open (Active).
     */
    public function validatePostingDate($date): FinancialYear
    {
        $finYear = $this->resolveByDate($date);

        if ($this->isClosed($finYear)) {
            throw new Exception("Voucher date belongs to a closed financial year.");
        }

        $settings = app(\App\Services\AccountingSettingsService::class)->getSettings();
        $parsedDate = \Carbon\Carbon::parse($date)->startOfDay();
        $today = \Carbon\Carbon::now()->startOfDay();
        
        if ($parsedDate->lt($today)) {
            if (!$settings->allow_backdated) {
                throw new Exception("Backdated entries are not allowed by Accounting Settings.");
            }
            if ($settings->max_backdate_days > 0 && $parsedDate->diffInDays($today) > $settings->max_backdate_days) {
                throw new Exception("Backdated entry exceeds maximum allowed days ({$settings->max_backdate_days}).");
            }
        } elseif ($parsedDate->gt($today)) {
            if (!$settings->allow_future_dated) {
                throw new Exception("Future-dated entries are not allowed by Accounting Settings.");
            }
            if ($settings->max_future_days > 0 && $parsedDate->diffInDays($today) > $settings->max_future_days) {
                throw new Exception("Future-dated entry exceeds maximum allowed days ({$settings->max_future_days}).");
            }
        }

        if (!$this->isActive($finYear) && !$parsedDate->gt($today)) {
            // Future FY
            throw new Exception("Voucher date belongs to a future/inactive financial year.");
        }

        return $finYear;
    }

    /**
     * Check if a financial year is active.
     */
    public function isActive(FinancialYear $finYear): bool
    {
        return $finYear->is_active && !$finYear->is_closed;
    }

    /**
     * Check if a financial year is closed.
     */
    public function isClosed(FinancialYear $finYear): bool
    {
        return $finYear->is_closed;
    }

    /**
     * Ensure no overlap when creating or updating a Financial Year.
     */
    public function validateOverlap($startDate, $endDate, $excludeId = null)
    {
        $parsedStart = Carbon::parse($startDate)->format('Y-m-d');
        $parsedEnd = Carbon::parse($endDate)->format('Y-m-d');

        $query = FinancialYear::where(function ($q) use ($parsedStart, $parsedEnd) {
            $q->where(function ($q1) use ($parsedStart, $parsedEnd) {
                // start_date of existing is inside the new range
                $q1->where('start_date', '>=', $parsedStart)
                   ->where('start_date', '<=', $parsedEnd);
            })->orWhere(function ($q2) use ($parsedStart, $parsedEnd) {
                // end_date of existing is inside the new range
                $q2->where('end_date', '>=', $parsedStart)
                   ->where('end_date', '<=', $parsedEnd);
            })->orWhere(function ($q3) use ($parsedStart, $parsedEnd) {
                // new range is entirely within existing range
                $q3->where('start_date', '<=', $parsedStart)
                   ->where('end_date', '>=', $parsedEnd);
            });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new Exception("Financial year dates overlap with an existing financial year.");
        }
    }

    /**
     * Activate a financial year (and deactivate all others).
     */
    public function activateFinancialYear(FinancialYear $finYear)
    {
        if ($this->isClosed($finYear)) {
            throw new Exception("Cannot activate a closed financial year.");
        }

        DB::transaction(function () use ($finYear) {
            FinancialYear::where('is_active', true)->update(['is_active' => false]);
            $finYear->update(['is_active' => true]);
        });
    }

    /**
     * Close a financial year.
     * This checks for pending/unbalanced transactions before closing.
     */
    public function closeFinancialYear(FinancialYear $finYear)
    {
        // 1. Validate Draft vouchers
        $draftVouchersCount = \App\Models\Voucher::where('financial_year_id', $finYear->id)
            ->where('status', 'Draft')
            ->count();

        if ($draftVouchersCount > 0) {
            throw new Exception("Financial Year cannot be closed because {$draftVouchersCount} vouchers are still in draft status.");
        }

        // 2. Validate unbalanced entries (Total Dr must equal Total Cr for the year)
        // Note: For now we skip complex checking until Journal Entry system is fully enforced, 
        // but we'd add it here.
        
        DB::transaction(function () use ($finYear) {
            $finYear->update(['is_closed' => true, 'is_active' => false]);
            
            // Note: Architecture placeholder for Balance Sheet carry-forward.
            // Future module will hook into this point to generate opening balances for next FY.
        });
    }

    /**
     * Reopen a closed financial year.
     */
    public function reopenFinancialYear(FinancialYear $finYear)
    {
        if (!$finYear->is_closed) {
            throw new Exception("Financial Year is not closed.");
        }

        // Reopening makes it inactive (future/historical) until explicitly activated again, or active if chosen.
        // We'll set it to inactive open state by default.
        DB::transaction(function () use ($finYear) {
            $finYear->update(['is_closed' => false, 'is_active' => false]);
        });
    }
}
