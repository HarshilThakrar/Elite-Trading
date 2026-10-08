<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\JournalEntry;
use App\Models\AccountGroup;
use Illuminate\Support\Facades\DB;

class BankBookService
{
    /**
     * Dynamically resolve Bank ledgers from the AccountGroup hierarchy.
     */
    public function getBankLedgers()
    {
        $bankGroup = AccountGroup::where('name', 'Bank Accounts')->first();
        
        if (!$bankGroup) {
            return collect();
        }

        $groupIds = $this->getAllChildGroupIds($bankGroup->id);
        $groupIds[] = $bankGroup->id;

        return Ledger::whereIn('account_group_id', $groupIds)->where('is_active', true)->orderBy('name')->get();
    }

    private function getAllChildGroupIds($parentId)
    {
        $children = AccountGroup::where('parent_id', $parentId)->pluck('id')->toArray();
        $allIds = $children;
        foreach ($children as $childId) {
            $allIds = array_merge($allIds, $this->getAllChildGroupIds($childId));
        }
        return $allIds;
    }

    /**
     * Get Opening Balance as on the given From Date.
     * Supports single ledger or "All Bank Accounts" (when ledgerId is 'all')
     */
    public function getOpeningBalance($ledgerId, $financialYear, $fromDate)
    {
        $ledgers = $ledgerId === 'all' ? $this->getBankLedgers() : collect([Ledger::findOrFail($ledgerId)]);
        
        $totalBalance = 0; // Accumulated as Dr positive, Cr negative

        foreach ($ledgers as $ledger) {
            $openingAmount = (float) $ledger->opening_balance;
            $openingType = $ledger->opening_balance_type;
            
            if ($financialYear) {
                $fyOpening = $ledger->getOpeningBalanceForYear($financialYear->id);
                if ($fyOpening) {
                    $openingAmount = (float) $fyOpening->amount;
                    $openingType = $fyOpening->type;
                } else {
                    if ($ledger->openingBalances()->exists()) {
                        $openingAmount = 0;
                        $openingType = 'Dr';
                    }
                }
            }
            
            $balance = ($openingType === 'Dr') ? $openingAmount : -$openingAmount;
            
            // Add transactions from FY Start Date up to (fromDate - 1 day)
            $entries = JournalEntry::where('ledger_id', $ledger->id)
                ->whereHas('voucher', function($q) use ($financialYear, $fromDate) {
                    $q->where('status', 'Posted')
                      ->where('financial_year_id', $financialYear->id)
                      ->where('date', '<', $fromDate);
                })
                ->get();
                
            foreach ($entries as $entry) {
                if ($entry->type === 'Dr') {
                    $balance += (float) $entry->amount;
                } else {
                    $balance -= (float) $entry->amount;
                }
            }

            $totalBalance += $balance;
        }
        
        return [
            'amount' => abs($totalBalance),
            'type' => $totalBalance >= 0 ? 'Dr' : 'Cr',
            'signed_amount' => $totalBalance
        ];
    }

    /**
     * Get Paginated Transactions Query.
     */
    public function getTransactionsQuery($ledgerId, $financialYearId, $fromDate, $toDate, $filters = [])
    {
        $ledgerIds = $ledgerId === 'all' ? $this->getBankLedgers()->pluck('id')->toArray() : [$ledgerId];

        $query = JournalEntry::select('journal_entries.*')
            ->join('vouchers', 'vouchers.id', '=', 'journal_entries.voucher_id')
            ->whereIn('journal_entries.ledger_id', $ledgerIds)
            ->where('vouchers.status', 'Posted')
            ->where('vouchers.financial_year_id', $financialYearId)
            ->whereBetween('vouchers.date', [$fromDate, $toDate]);
            
        if (!empty($filters['voucher_type'])) {
            $query->where('vouchers.type', $filters['voucher_type']);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('vouchers.voucher_number', 'like', "%{$search}%")
                  ->orWhere('vouchers.narration', 'like', "%{$search}%")
                  ->orWhere('vouchers.reference_id', 'like', "%{$search}%")
                  ->orWhere('journal_entries.narration', 'like', "%{$search}%");
            });
        }

        // Deterministic ordering
        $query->orderBy('vouchers.date', 'asc')
              ->orderBy('vouchers.id', 'asc')
              ->orderBy('journal_entries.id', 'asc');
              
        $query->with(['voucher', 'voucher.entries.ledger', 'ledger']);
        
        return $query;
    }

    /**
     * Deduce opposite ledgers for "Particulars" column.
     */
    public function getParticulars(JournalEntry $entry)
    {
        $voucher = $entry->voucher;
        if (!$voucher) return '-';

        $oppositeEntries = $voucher->entries->filter(function($e) use ($entry) {
            return $e->id !== $entry->id && $e->type !== $entry->type;
        });

        if ($oppositeEntries->count() === 1) {
            return $oppositeEntries->first()->ledger->name ?? '-';
        } elseif ($oppositeEntries->count() > 1) {
            $names = $oppositeEntries->map(function($e) { return $e->ledger->name ?? 'Unknown'; })->toArray();
            return implode(', ', $names);
        }
        
        $otherEntries = $voucher->entries->filter(function($e) use ($entry) {
            return $e->id !== $entry->id;
        });
        
        if ($otherEntries->count() > 0) {
             return $otherEntries->first()->ledger->name ?? '-';
        }

        return $entry->ledger->name ?? '-';
    }

    /**
     * Calculate summary totals for the date range
     */
    public function getSummary($ledgerId, $financialYearId, $fromDate, $toDate, $filters = [])
    {
        $query = $this->getTransactionsQuery($ledgerId, $financialYearId, $fromDate, $toDate, $filters);
        
        // Execute a sum query without fetching models
        $totals = (clone $query)->reorder()->select(
            DB::raw("SUM(CASE WHEN journal_entries.type = 'Dr' THEN journal_entries.amount ELSE 0 END) as total_receipts"),
            DB::raw("SUM(CASE WHEN journal_entries.type = 'Cr' THEN journal_entries.amount ELSE 0 END) as total_payments")
        )->first();

        return [
            'receipts' => (float) ($totals->total_receipts ?? 0),
            'payments' => (float) ($totals->total_payments ?? 0)
        ];
    }
}
