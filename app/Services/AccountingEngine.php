<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingEngine
{
    /**
     * Post a new voucher with balanced double-entry accounting entries.
     *
     * @param array $voucherData Data for the voucher (type, date, narration, financial_year_id, etc.)
     * @param array $entries Array of journal entry data. Each must have 'ledger_id', 'type' (Dr/Cr), 'amount'
     * @return Voucher
     * @throws Exception if debits and credits do not match
     */
    public function postVoucher(array $voucherData, array $entries)
    {
        return DB::transaction(function () use ($voucherData, $entries) {
            
            // IDEMPOTENCY CHECK
            if (isset($voucherData['reference_id']) && isset($voucherData['reference_type']) && isset($voucherData['type'])) {
                $exists = Voucher::where('reference_id', $voucherData['reference_id'])
                                 ->where('reference_type', $voucherData['reference_type'])
                                 ->where('type', $voucherData['type'])
                                 ->exists();
                if ($exists) {
                    throw new Exception("Duplicate Voucher: A voucher of type {$voucherData['type']} already exists for this reference.");
                }
            }

            $debitTotal = 0;
            $creditTotal = 0;

            foreach ($entries as $entry) {
                if ($entry['type'] === 'Dr') {
                    $debitTotal += (float) $entry['amount'];
                } elseif ($entry['type'] === 'Cr') {
                    $creditTotal += (float) $entry['amount'];
                } else {
                    throw new Exception("Invalid entry type. Must be 'Dr' or 'Cr'.");
                }
            }

            // The core rule of double-entry accounting
            if (round($debitTotal, 2) !== round($creditTotal, 2)) {
                throw new Exception("Accounting mismatch: Total Debits (\${$debitTotal}) do not equal Total Credits (\${$creditTotal}).");
            }

            // Generate voucher number if not provided
            if (empty($voucherData['voucher_number'])) {
                $voucherNumberService = app(\App\Services\VoucherNumberService::class);
                $voucherData['voucher_number'] = $voucherNumberService->generateNextNumber($voucherData['type'], $voucherData['date']);
            }

            $voucher = Voucher::create($voucherData);

            foreach ($entries as $entry) {
                $voucher->entries()->create([
                    'ledger_id' => $entry['ledger_id'],
                    'type' => $entry['type'],
                    'amount' => $entry['amount'],
                    'narration' => $entry['narration'] ?? null,
                    'cost_centre_id' => $entry['cost_centre_id'] ?? null
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Get Ledger Balance
     */
    public function getLedgerBalance($ledgerId, $financialYearId = null)
    {
        $ledger = \App\Models\Ledger::findOrFail($ledgerId);
        
        $entryQuery = JournalEntry::where('ledger_id', $ledgerId)
            ->whereHas('voucher', function($q) use ($financialYearId) {
                $q->where('status', 'Posted');
                if ($financialYearId) {
                    $q->where('financial_year_id', $financialYearId);
                }
            });
            
        $debits = (clone $entryQuery)->where('type', 'Dr')->sum('amount');
        $credits = (clone $entryQuery)->where('type', 'Cr')->sum('amount');
        
        $balance = 0;
        
        // Use authoritative opening balance if a financial year is requested
        $openingAmount = $ledger->opening_balance;
        $openingType = $ledger->opening_balance_type;
        
        if ($financialYearId) {
            $fyOpening = $ledger->getOpeningBalanceForYear($financialYearId);
            if ($fyOpening) {
                $openingAmount = $fyOpening->amount;
                $openingType = $fyOpening->type;
            } else {
                // If requested FY has no opening balance record, default to 0 to avoid legacy overlap
                // unless it's explicitly desired. We'll fallback to legacy if there is NO opening balances across ANY year.
                // But a safer approach: fallback to legacy ledgers.opening_balance ONLY if there are 0 records in opening_balances for this ledger.
                $hasAnyFYBalance = $ledger->openingBalances()->exists();
                if ($hasAnyFYBalance) {
                    $openingAmount = 0;
                    $openingType = 'Dr';
                }
            }
        }
        
        if ($openingType === 'Dr') {
            $balance = $openingAmount + $debits - $credits;
        } else {
            $balance = $openingAmount + $credits - $debits;
        }
        
        return [
            'balance' => abs($balance),
            'type' => $balance >= 0 ? $openingType : ($openingType === 'Dr' ? 'Cr' : 'Dr')
        ];
    }
}
