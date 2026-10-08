<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Ledger;
use App\Models\JournalEntry;
use App\Models\BankStatementTransaction;

class BankReconciliationService
{
    /**
     * Get un-reconciled book (ERP) transactions for a given bank ledger.
     */
    public function getBookTransactions($ledgerId, $fromDate, $toDate)
    {
        // A book transaction is a JournalEntry for this bank ledger
        // from a POSTED voucher, that does NOT have a matched BankStatementTransaction.
        // Or if we want to show all, we can include reconciled.
        return JournalEntry::with('voucher')
            ->where('ledger_id', $ledgerId)
            ->whereHas('voucher', function($q) use ($fromDate, $toDate) {
                $q->where('status', 'Posted');
                if ($fromDate) $q->whereDate('date', '>=', $fromDate);
                if ($toDate) $q->whereDate('date', '<=', $toDate);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get un-reconciled bank statement transactions for a given ledger.
     */
    public function getBankTransactions($ledgerId, $fromDate, $toDate)
    {
        $query = BankStatementTransaction::where('bank_ledger_id', $ledgerId);
        
        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }
        
        return $query->orderBy('transaction_date', 'desc')->get();
    }

    /**
     * Match a book transaction (JournalEntry) with a bank statement transaction.
     */
    public function matchTransaction($journalEntryId, $bankStatementId)
    {
        return DB::transaction(function () use ($journalEntryId, $bankStatementId) {
            $entry = JournalEntry::findOrFail($journalEntryId);
            $stmt = BankStatementTransaction::findOrFail($bankStatementId);

            // Validation
            if ($entry->ledger_id != $stmt->bank_ledger_id) {
                throw new Exception("Ledger mismatch between book and bank transaction.");
            }
            if ($stmt->reconciliation_status === 'Reconciled') {
                throw new Exception("Bank transaction is already reconciled.");
            }

            // Check if JournalEntry is already matched to something else
            $existingMatch = BankStatementTransaction::where('matched_journal_entry_id', $journalEntryId)->first();
            if ($existingMatch) {
                throw new Exception("Book transaction is already reconciled with another bank statement row.");
            }

            // Amount Validation (Exact amount required)
            // Book entry type: Dr means Bank Balance increased. Cr means Bank Balance decreased.
            // Bank stmt type: credit_amount > 0 means Bank Balance increased (deposit). debit_amount > 0 means Bank Balance decreased (withdrawal).
            $bookAmount = (float)$entry->amount;
            $bankAmount = 0;
            
            if ($entry->type === 'Dr') {
                // Should match a bank credit (deposit)
                $bankAmount = (float)$stmt->credit_amount;
            } else {
                // Should match a bank debit (withdrawal)
                $bankAmount = (float)$stmt->debit_amount;
            }

            if (abs($bookAmount - $bankAmount) > 0.01) {
                throw new Exception("Amounts do not match exactly. Book: {$bookAmount}, Bank: {$bankAmount}");
            }

            $stmt->reconciliation_status = 'Reconciled';
            $stmt->matched_journal_entry_id = $entry->id;
            $stmt->matched_at = now();
            $stmt->matched_by = auth()->id();
            $stmt->save();

            return true;
        });
    }

    /**
     * Unmatch a transaction
     */
    public function unmatchTransaction($bankStatementId)
    {
        return DB::transaction(function () use ($bankStatementId) {
            $stmt = BankStatementTransaction::findOrFail($bankStatementId);
            
            if ($stmt->reconciliation_status !== 'Reconciled') {
                throw new Exception("Transaction is not reconciled.");
            }

            $stmt->reconciliation_status = 'Unreconciled';
            $stmt->matched_journal_entry_id = null;
            $stmt->matched_at = null;
            $stmt->matched_by = null;
            $stmt->save();

            return true;
        });
    }

    /**
     * Auto Match Engine
     */
    public function autoMatch($ledgerId, $toleranceDays = 3)
    {
        $matches = 0;
        
        DB::transaction(function () use ($ledgerId, $toleranceDays, &$matches) {
            // Get all unreconciled bank transactions for this ledger
            $unreconciledBank = BankStatementTransaction::where('bank_ledger_id', $ledgerId)
                ->where('reconciliation_status', 'Unreconciled')
                ->get();

            // Get all unreconciled book transactions (Journal Entries)
            // We use a subquery to find ones not in bank_statement_transactions
            $matchedIds = BankStatementTransaction::whereNotNull('matched_journal_entry_id')
                            ->pluck('matched_journal_entry_id')->toArray();
                            
            $unreconciledBook = JournalEntry::with('voucher')
                ->where('ledger_id', $ledgerId)
                ->whereNotIn('id', $matchedIds)
                ->whereHas('voucher', function($q) {
                    $q->where('status', 'Posted');
                })
                ->get();

            foreach ($unreconciledBank as $bankTxn) {
                // Priority 1: Exact Amount + Exact Reference (if exists) + Similar Date
                // Priority 2: Exact Amount + Exact Date
                
                $bankDate = \Carbon\Carbon::parse($bankTxn->transaction_date);
                $isDeposit = $bankTxn->credit_amount > 0;
                $targetAmount = $isDeposit ? $bankTxn->credit_amount : $bankTxn->debit_amount;
                $targetType = $isDeposit ? 'Dr' : 'Cr'; // In ERP, Dr to bank is a deposit
                
                // Find candidates
                $candidates = $unreconciledBook->filter(function ($bookTxn) use ($targetAmount, $targetType, $bankDate, $toleranceDays) {
                    $bookDate = \Carbon\Carbon::parse($bookTxn->voucher->date);
                    $daysDiff = $bookDate->diffInDays($bankDate);
                    
                    return $bookTxn->type === $targetType 
                        && abs($bookTxn->amount - $targetAmount) < 0.01
                        && $daysDiff <= $toleranceDays;
                });

                if ($candidates->count() === 1) {
                    $match = $candidates->first();
                    // Additional check on reference if both have it
                    $bankRef = trim($bankTxn->reference_number);
                    $bookRef = trim($match->voucher->reference_number ?? '');
                    
                    if ($bankRef && $bookRef && strcasecmp($bankRef, $bookRef) !== 0) {
                        // References exist but don't match exactly. Skip for safety, leave to manual.
                        continue;
                    }
                    
                    // Match found
                    $this->matchTransaction($match->id, $bankTxn->id);
                    $matches++;
                    
                    // Remove from unreconciledBook list to prevent double matching in same pass
                    $unreconciledBook = $unreconciledBook->reject(function ($item) use ($match) {
                        return $item->id === $match->id;
                    });
                }
            }
        });
        
        return $matches;
    }

    /**
     * Calculate summary
     */
    public function getSummary($ledgerId, $fromDate = null, $toDate = null)
    {
        // 1. Book Balance Calculation (Sum of all posted entries up to To Date)
        $bookQuery = JournalEntry::where('ledger_id', $ledgerId)
            ->whereHas('voucher', function($q) use ($toDate) {
                $q->where('status', 'Posted');
                if ($toDate) $q->whereDate('date', '<=', $toDate);
            });
            
        $bookDr = (clone $bookQuery)->where('type', 'Dr')->sum('amount');
        $bookCr = (clone $bookQuery)->where('type', 'Cr')->sum('amount');
        $bookBalance = $bookDr - $bookCr; // Positive means debit balance (positive bank balance)

        // 2. Bank Balance (Last running balance or calculated)
        $bankTxnsQuery = BankStatementTransaction::where('bank_ledger_id', $ledgerId);
        if ($toDate) {
            $bankTxnsQuery->whereDate('transaction_date', '<=', $toDate);
        }
        $bankCrSum = (clone $bankTxnsQuery)->sum('credit_amount'); // Deposits
        $bankDrSum = (clone $bankTxnsQuery)->sum('debit_amount'); // Withdrawals
        
        // Let's rely on calculating the bank balance from the imported rows.
        // Assuming opening balance is 0 for bank statement table, unless we import it.
        // In real world, bank balance is best taken from the latest imported row's running balance, 
        // but if that's unreliable, we use:
        $bankBalance = $bankCrSum - $bankDrSum;
        
        $latestTxn = (clone $bankTxnsQuery)->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->first();
        if ($latestTxn && $latestTxn->running_balance !== null) {
            $bankBalance = $latestTxn->running_balance;
        }

        // 3. Unreconciled Book Transactions
        $matchedIds = BankStatementTransaction::where('bank_ledger_id', $ledgerId)
                            ->whereNotNull('matched_journal_entry_id')
                            ->pluck('matched_journal_entry_id')->toArray();
                            
        $unreconciledBookQuery = JournalEntry::where('ledger_id', $ledgerId)
            ->whereNotIn('id', $matchedIds)
            ->whereHas('voucher', function($q) use ($toDate) {
                $q->where('status', 'Posted');
                if ($toDate) $q->whereDate('date', '<=', $toDate);
            });
            
        $unreconciledBookDr = (clone $unreconciledBookQuery)->where('type', 'Dr')->sum('amount'); // Deposits in transit
        $unreconciledBookCr = (clone $unreconciledBookQuery)->where('type', 'Cr')->sum('amount'); // Unpresented cheques
        $unreconciledBookNet = $unreconciledBookDr - $unreconciledBookCr;

        // 4. Unreconciled Bank Transactions
        $unreconciledBankQuery = BankStatementTransaction::where('bank_ledger_id', $ledgerId)
            ->where('reconciliation_status', 'Unreconciled');
        if ($toDate) {
            $unreconciledBankQuery->whereDate('transaction_date', '<=', $toDate);
        }
        $unrecBankCr = (clone $unreconciledBankQuery)->sum('credit_amount'); // Bank credits not in ERP
        $unrecBankDr = (clone $unreconciledBankQuery)->sum('debit_amount'); // Bank debits not in ERP
        $unreconciledBankNet = $unrecBankCr - $unrecBankDr;

        // Calculation check
        // Reconciled Book Balance = Book Balance - Unreconciled Book
        // Reconciled Bank Balance = Bank Balance - Unreconciled Bank
        // The difference should ideally be 0 if everything is matched up to date.
        
        $reconciledBookBalance = $bookBalance - $unreconciledBookNet;
        $reconciledBankBalance = $bankBalance - $unreconciledBankNet;
        $difference = $reconciledBookBalance - $reconciledBankBalance;

        return [
            'book_balance' => $bookBalance,
            'bank_balance' => $bankBalance,
            'unreconciled_book' => $unreconciledBookNet,
            'unreconciled_bank' => $unreconciledBankNet,
            'reconciled_book' => $reconciledBookBalance,
            'reconciled_bank' => $reconciledBankBalance,
            'difference' => $difference,
            
            // Detail counts
            'unrec_book_dr' => $unreconciledBookDr,
            'unrec_book_cr' => $unreconciledBookCr,
            'unrec_bank_cr' => $unrecBankCr, // Credits to bank = deposits
            'unrec_bank_dr' => $unrecBankDr, // Debits to bank = withdrawals
        ];
    }
}
