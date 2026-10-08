<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\JournalEntry;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;

class OutstandingService
{
    /**
     * Get Outstanding Data for Receivables or Payables.
     *
     * @param string $type 'receivables' or 'payables'
     * @param \App\Models\FinancialYear $financialYear
     * @param string $asOfDate
     * @param array $filters
     * @return array
     */
    public function getOutstandingData($type, $financialYear, $asOfDate, $filters = [])
    {
        $ledgers = $this->getRelevantLedgers($type);
        
        $data = [];
        $totalOutstanding = 0;
        $agingTotals = [
            'not_due' => 0,
            '0_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_180' => 0,
            '181_365' => 0,
            'above_365' => 0,
        ];
        
        $overdueAmount = 0;
        $partyCount = 0;

        $targetLedgerId = $filters['ledger_id'] ?? null;
        $search = $filters['search'] ?? null;

        foreach ($ledgers as $ledger) {
            // Apply ledger specific filters
            if ($targetLedgerId && $ledger->id != $targetLedgerId) {
                continue;
            }
            if ($search && stripos($ledger->name, $search) === false) {
                continue;
            }

            $partyOutstanding = $this->calculateFifoOutstanding($ledger, $type, $financialYear, $asOfDate);
            
            if ($partyOutstanding['total_outstanding'] > 0) {
                $partyCount++;
                $totalOutstanding += $partyOutstanding['total_outstanding'];
                $overdueAmount += $partyOutstanding['total_overdue'];
                
                foreach ($partyOutstanding['aging_summary'] as $key => $amount) {
                    $agingTotals[$key] += $amount;
                }
                
                $data[] = $partyOutstanding;
            }
        }

        return [
            'type' => $type,
            'financialYear' => $financialYear,
            'asOfDate' => $asOfDate,
            'data' => $data,
            'summary' => [
                'total_outstanding' => $totalOutstanding,
                'total_overdue' => $overdueAmount,
                'party_count' => $partyCount,
                'aging' => $agingTotals
            ]
        ];
    }

    protected function getRelevantLedgers($type)
    {
        $groupRoots = [];
        if ($type === 'receivables') {
            // Sundry Debtors
            $groupRoots = \App\Models\AccountGroup::where('name', 'like', '%Sundry Debtor%')
                                ->orWhere('name', 'like', '%Current Asset%')
                                ->pluck('id')->toArray();
        } else {
            // Sundry Creditors
            $groupRoots = \App\Models\AccountGroup::where('name', 'like', '%Sundry Creditor%')
                                ->orWhere('name', 'like', '%Current Liabilit%')
                                ->pluck('id')->toArray();
        }

        if (empty($groupRoots)) {
            return collect();
        }

        $allGroups = \App\Models\AccountGroup::all();
        $relevantGroupIds = $this->getAllDescendantGroups($groupRoots, $allGroups);

        return Ledger::whereIn('account_group_id', $relevantGroupIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function getAllDescendantGroups($groupRoots, $allGroups)
    {
        $result = $groupRoots;
        $children = $allGroups->whereIn('parent_id', $groupRoots)->pluck('id')->toArray();
        
        if (!empty($children)) {
            $result = array_merge($result, $this->getAllDescendantGroups($children, $allGroups));
        }
        
        return array_unique($result);
    }

    /**
     * Calculate FIFO outstanding bills for a specific ledger.
     */
    protected function calculateFifoOutstanding(Ledger $ledger, $type, $financialYear, $asOfDate)
    {
        $asOfDateObj = Carbon::parse($asOfDate)->endOfDay();
        $fyStartDate = Carbon::parse($financialYear->start_date)->startOfDay();

        // 1. Get Opening Balance for the selected FY
        $openingBalanceAmount = 0;
        $openingBalanceType = 'Dr';
        
        $fyOpening = $ledger->getOpeningBalanceForYear($financialYear->id);
        if ($fyOpening) {
            $openingBalanceAmount = (float)$fyOpening->amount;
            $openingBalanceType = $fyOpening->type;
        } else {
            // Fallback if no specific FY opening balances exist
            if (!$ledger->openingBalances()->exists()) {
                $openingBalanceAmount = (float)$ledger->opening_balance;
                $openingBalanceType = $ledger->opening_balance_type;
            }
        }

        // 2. Fetch all Journal Entries for this ledger in the current FY up to As Of Date
        $entries = JournalEntry::with(['voucher'])
            ->where('ledger_id', $ledger->id)
            ->whereHas('voucher', function($q) use ($financialYear, $asOfDate) {
                $q->where('status', 'Posted')
                  ->where('financial_year_id', $financialYear->id)
                  ->where('date', '<=', $asOfDate);
            })
            ->get()
            ->sortBy(function($entry) {
                return $entry->voucher->date->timestamp . '-' . $entry->id;
            });

        $invoiceType = $type === 'receivables' ? 'Dr' : 'Cr';
        $paymentType = $type === 'receivables' ? 'Cr' : 'Dr';

        $invoices = [];
        $totalPayments = 0;

        // 3. Setup Opening Balance as the first Invoice or Advance Payment
        if ($openingBalanceAmount > 0) {
            if ($openingBalanceType === $invoiceType) {
                $invoices[] = [
                    'is_opening' => true,
                    'voucher_number' => 'Opening Balance',
                    'date' => $fyStartDate,
                    'due_date' => $fyStartDate, // Opening balance is immediately due
                    'amount' => $openingBalanceAmount,
                    'paid' => 0,
                    'outstanding' => $openingBalanceAmount
                ];
            } else {
                $totalPayments += $openingBalanceAmount;
            }
        }

        // Try to get payment terms if linked to a customer/vendor
        $paymentTermsDays = 0;
        if ($type === 'receivables') {
            $customer = Customer::where('company_name', $ledger->name)->first();
            if ($customer && is_numeric($customer->payment_terms)) {
                $paymentTermsDays = (int)$customer->payment_terms;
            }
        } else {
            $vendor = Vendor::where('company_name', $ledger->name)->first();
            if ($vendor && is_numeric($vendor->payment_terms)) {
                $paymentTermsDays = (int)$vendor->payment_terms;
            }
        }

        // 4. Process Journal Entries
        foreach ($entries as $entry) {
            if ($entry->type === $invoiceType) {
                $vDate = Carbon::parse($entry->voucher->date);
                $dueDate = $vDate->copy()->addDays($paymentTermsDays);
                
                $invoices[] = [
                    'is_opening' => false,
                    'voucher_id' => $entry->voucher->id,
                    'voucher_number' => $entry->voucher->voucher_number,
                    'type' => $entry->voucher->type,
                    'date' => $vDate,
                    'due_date' => $dueDate,
                    'amount' => (float)$entry->amount,
                    'paid' => 0,
                    'outstanding' => (float)$entry->amount
                ];
            } else {
                $totalPayments += (float)$entry->amount;
            }
        }

        // 5. Apply FIFO Allocation
        $remainingPayments = $totalPayments;
        $outstandingInvoices = [];
        $partyTotalOutstanding = 0;
        $partyTotalOverdue = 0;
        $agingSummary = [
            'not_due' => 0,
            '0_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_180' => 0,
            '181_365' => 0,
            'above_365' => 0,
        ];

        foreach ($invoices as &$inv) {
            if ($remainingPayments >= $inv['amount']) {
                $inv['paid'] = $inv['amount'];
                $inv['outstanding'] = 0;
                $remainingPayments -= $inv['amount'];
            } elseif ($remainingPayments > 0) {
                $inv['paid'] = $remainingPayments;
                $inv['outstanding'] -= $remainingPayments;
                $remainingPayments = 0;
            }

            if ($inv['outstanding'] > 0.01) { // Floating point safety
                $days = $asOfDateObj->diffInDays($inv['due_date'], false); // Negative if overdue
                
                $bucket = 'not_due';
                if ($days < 0) {
                    $partyTotalOverdue += $inv['outstanding'];
                    $overdueDays = abs($days);
                    if ($overdueDays <= 30) $bucket = '0_30';
                    elseif ($overdueDays <= 60) $bucket = '31_60';
                    elseif ($overdueDays <= 90) $bucket = '61_90';
                    elseif ($overdueDays <= 180) $bucket = '91_180';
                    elseif ($overdueDays <= 365) $bucket = '181_365';
                    else $bucket = 'above_365';
                }
                
                $inv['days'] = $days < 0 ? abs($days) : 0;
                $inv['bucket'] = $bucket;
                
                $agingSummary[$bucket] += $inv['outstanding'];
                $partyTotalOutstanding += $inv['outstanding'];
                $outstandingInvoices[] = $inv;
            }
        }

        // Handle unallocated advances (when payments > invoices)
        if ($remainingPayments > 0.01) {
            $outstandingInvoices[] = [
                'is_opening' => false,
                'voucher_number' => 'Unallocated Advance / Excess',
                'date' => $asOfDateObj,
                'due_date' => $asOfDateObj,
                'amount' => 0,
                'paid' => $remainingPayments,
                'outstanding' => -$remainingPayments, // Negative outstanding denotes advance
                'days' => 0,
                'bucket' => 'not_due'
            ];
            $partyTotalOutstanding -= $remainingPayments;
            $agingSummary['not_due'] -= $remainingPayments;
        }

        return [
            'ledger' => $ledger,
            'invoices' => $outstandingInvoices,
            'total_outstanding' => $partyTotalOutstanding,
            'total_overdue' => $partyTotalOverdue,
            'aging_summary' => $agingSummary
        ];
    }
}
