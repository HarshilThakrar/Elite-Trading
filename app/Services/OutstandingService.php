<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\JournalEntry;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\AccountGroup;
use App\Models\OpeningBalance;
use Carbon\Carbon;

class OutstandingService
{
    /**
     * Get Outstanding Data for Receivables or Payables (Batch-loaded & Optimized).
     *
     * @param string $type 'receivables' or 'payables'
     * @param \App\Models\FinancialYear $financialYear
     * @param string $asOfDate
     * @param array $filters
     * @return array
     */
    public function getOutstandingData($type, $financialYear, $asOfDate, $filters = [])
    {
        $ledgers = $this->getRelevantLedgers($type, $filters);
        
        $emptyReport = [
            'type' => $type,
            'financialYear' => $financialYear,
            'asOfDate' => $asOfDate,
            'data' => [],
            'summary' => [
                'total_outstanding' => 0,
                'total_overdue' => 0,
                'party_count' => 0,
                'aging' => [
                    'not_due' => 0,
                    '0_30' => 0,
                    '31_60' => 0,
                    '61_90' => 0,
                    '91_180' => 0,
                    '181_365' => 0,
                    'above_365' => 0,
                ]
            ]
        ];

        if ($ledgers->isEmpty() || !$financialYear) {
            return $emptyReport;
        }

        $ledgerIds = $ledgers->pluck('id')->toArray();
        $asOfDateObj = Carbon::parse($asOfDate)->endOfDay();
        $fyStartDate = Carbon::parse($financialYear->start_date)->startOfDay();

        // 1. Batch load Opening Balances for the selected FY (1 query)
        $openingBalances = OpeningBalance::where('financial_year_id', $financialYear->id)
            ->whereIn('ledger_id', $ledgerIds)
            ->get()
            ->keyBy('ledger_id');

        // Check which ledgers have ANY FY opening balance record (to decide fallback)
        $ledgersWithAnyOb = OpeningBalance::whereIn('ledger_id', $ledgerIds)
            ->pluck('ledger_id')
            ->flip();

        // 2. Batch load payment terms (1 query)
        $paymentTermsMap = [];
        if ($type === 'receivables') {
            $customerIds = $ledgers->where('type', 'customer')->pluck('reference_id')->filter()->unique();
            $customers = Customer::whereIn('id', $customerIds)
                ->orWhereIn('company_name', $ledgers->pluck('name'))
                ->get();
            
            $termsById = $customers->keyBy('id');
            $termsByName = $customers->keyBy(fn($c) => strtolower(trim($c->company_name)));

            foreach ($ledgers as $l) {
                $terms = 0;
                if ($l->type === 'customer' && $l->reference_id && isset($termsById[$l->reference_id])) {
                    $terms = (int)($termsById[$l->reference_id]->payment_terms ?? 0);
                } elseif (isset($termsByName[strtolower(trim($l->name))])) {
                    $terms = (int)($termsByName[strtolower(trim($l->name))]->payment_terms ?? 0);
                }
                $paymentTermsMap[$l->id] = max(0, $terms);
            }
        } else {
            $vendorIds = $ledgers->where('type', 'vendor')->pluck('reference_id')->filter()->unique();
            $vendors = Vendor::whereIn('id', $vendorIds)
                ->orWhereIn('company_name', $ledgers->pluck('name'))
                ->get();
            
            $termsById = $vendors->keyBy('id');
            $termsByName = $vendors->keyBy(fn($v) => strtolower(trim($v->company_name)));

            foreach ($ledgers as $l) {
                $terms = 0;
                if ($l->type === 'vendor' && $l->reference_id && isset($termsById[$l->reference_id])) {
                    $terms = (int)($termsById[$l->reference_id]->lead_time_days ?? 0);
                } elseif (isset($termsByName[strtolower(trim($l->name))])) {
                    $terms = (int)($termsByName[strtolower(trim($l->name))]->lead_time_days ?? 0);
                }
                $paymentTermsMap[$l->id] = max(0, $terms);
            }
        }

        // 3. Batch load all posted journal entries with vouchers for all target ledgers (2 queries)
        $entriesGrouped = JournalEntry::with(['voucher' => function($q) {
                $q->select('id', 'voucher_number', 'type', 'date', 'status', 'financial_year_id');
            }])
            ->whereIn('ledger_id', $ledgerIds)
            ->whereHas('voucher', function($q) use ($financialYear, $asOfDate) {
                $q->where('status', 'Posted')
                  ->where('financial_year_id', $financialYear->id)
                  ->where('date', '<=', $asOfDate);
            })
            ->get()
            ->groupBy('ledger_id');

        // 4. In-memory FIFO processing across all ledgers
        $data = [];
        $totalOutstanding = 0;
        $overdueAmount = 0;
        $partyCount = 0;
        $agingTotals = [
            'not_due' => 0,
            '0_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_180' => 0,
            '181_365' => 0,
            'above_365' => 0,
        ];

        $invoiceType = $type === 'receivables' ? 'Dr' : 'Cr';
        $paymentType = $type === 'receivables' ? 'Cr' : 'Dr';

        foreach ($ledgers as $ledger) {
            // Determine opening balance
            $openingBalanceAmount = 0;
            $openingBalanceType = 'Dr';
            if (isset($openingBalances[$ledger->id])) {
                $ob = $openingBalances[$ledger->id];
                $openingBalanceAmount = (float)$ob->amount;
                $openingBalanceType = $ob->type;
            } elseif (!isset($ledgersWithAnyOb[$ledger->id])) {
                $openingBalanceAmount = (float)$ledger->opening_balance;
                $openingBalanceType = $ledger->opening_balance_type;
            }

            $invoices = [];
            $totalPayments = 0;

            if ($openingBalanceAmount > 0) {
                if ($openingBalanceType === $invoiceType) {
                    $invoices[] = [
                        'is_opening' => true,
                        'voucher_number' => 'Opening Balance',
                        'date' => $fyStartDate,
                        'due_date' => $fyStartDate,
                        'amount' => $openingBalanceAmount,
                        'paid' => 0,
                        'outstanding' => $openingBalanceAmount
                    ];
                } else {
                    $totalPayments += $openingBalanceAmount;
                }
            }

            $paymentTermsDays = $paymentTermsMap[$ledger->id] ?? 0;
            $ledgerEntries = $entriesGrouped->get($ledger->id, collect());

            // Sort entries chronologically
            $sortedEntries = $ledgerEntries->sortBy(function($entry) {
                $ts = $entry->voucher && $entry->voucher->date ? $entry->voucher->date->timestamp : 0;
                return $ts . '-' . $entry->id;
            });

            foreach ($sortedEntries as $entry) {
                if (!$entry->voucher) {
                    continue;
                }
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

            // Skip ledgers with no activity and zero opening
            if (empty($invoices) && $totalPayments <= 0.01) {
                continue;
            }

            // Apply FIFO Allocation
            $remainingPayments = $totalPayments;
            $outstandingInvoices = [];
            $partyTotalOutstanding = 0;
            $partyTotalOverdue = 0;
            $partyAging = [
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

                if ($inv['outstanding'] > 0.01) {
                    $asOfDateStart = Carbon::parse($asOfDate)->startOfDay();
                    $dueDateStart = Carbon::parse($inv['due_date'])->startOfDay();

                    $bucket = 'not_due';
                    $overdueDays = 0;

                    if ($asOfDateStart->greaterThan($dueDateStart)) {
                        $overdueDays = (int)$dueDateStart->diffInDays($asOfDateStart);
                        $partyTotalOverdue += $inv['outstanding'];
                        
                        if ($overdueDays <= 30) $bucket = '0_30';
                        elseif ($overdueDays <= 60) $bucket = '31_60';
                        elseif ($overdueDays <= 90) $bucket = '61_90';
                        elseif ($overdueDays <= 180) $bucket = '91_180';
                        elseif ($overdueDays <= 365) $bucket = '181_365';
                        else $bucket = 'above_365';
                    }

                    $inv['days'] = $overdueDays;
                    $inv['bucket'] = $bucket;

                    $partyAging[$bucket] += $inv['outstanding'];
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
                    'outstanding' => -$remainingPayments,
                    'days' => 0,
                    'bucket' => 'not_due'
                ];
                $partyTotalOutstanding -= $remainingPayments;
                $partyAging['not_due'] -= $remainingPayments;
            }

            // Include party if there is outstanding amount or invoices
            if (abs($partyTotalOutstanding) > 0.01 || count($outstandingInvoices) > 0) {
                $partyCount++;
                $totalOutstanding += $partyTotalOutstanding;
                $overdueAmount += $partyTotalOverdue;

                foreach ($partyAging as $k => $amt) {
                    $agingTotals[$k] += $amt;
                }

                $data[] = [
                    'ledger' => $ledger,
                    'invoices' => $outstandingInvoices,
                    'total_outstanding' => $partyTotalOutstanding,
                    'total_overdue' => $partyTotalOverdue,
                    'aging_summary' => $partyAging
                ];
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

    /**
     * Get relevant Ledgers for Receivables (Debtors) or Payables (Creditors).
     * Strictly avoids pulling unrelated asset/liability accounts like Cash, Bank, Taxes.
     */
    protected function getRelevantLedgers($type, $filters = [])
    {
        $allGroups = AccountGroup::all();
        $groupRoots = [];

        if ($type === 'receivables') {
            $groupRoots = $allGroups->filter(function($g) {
                $n = strtolower($g->name);
                return (str_contains($n, 'sundry debtor') || str_contains($n, 'debtor') || str_contains($n, 'customer') || str_contains($n, 'receivable'))
                    && !str_contains($n, 'asset') && !str_contains($n, 'bank') && !str_contains($n, 'cash');
            })->pluck('id')->toArray();
        } else {
            $groupRoots = $allGroups->filter(function($g) {
                $n = strtolower($g->name);
                return (str_contains($n, 'sundry creditor') || str_contains($n, 'creditor') || str_contains($n, 'vendor') || str_contains($n, 'supplier') || str_contains($n, 'payable'))
                    && !str_contains($n, 'liabilit') && !str_contains($n, 'tax') && !str_contains($n, 'duty');
            })->pluck('id')->toArray();
        }

        $relevantGroupIds = $this->getAllDescendantGroups($groupRoots, $allGroups);

        $query = Ledger::where('is_active', true)
            ->where(function($q) use ($type, $relevantGroupIds) {
                if (!empty($relevantGroupIds)) {
                    $q->whereIn('account_group_id', $relevantGroupIds);
                }
                if ($type === 'receivables') {
                    $q->orWhere('type', 'customer');
                } else {
                    $q->orWhere('type', 'vendor');
                }
            });

        // Strictly exclude Cash, Bank, and Tax ledgers
        $excludedGroupIds = $allGroups->filter(function($g) {
            $n = strtolower($g->name);
            return in_array($n, ['bank accounts', 'cash-in-hand', 'duties & taxes', 'direct expenses', 'direct income', 'indirect expenses', 'indirect income']);
        })->pluck('id')->toArray();

        if (!empty($excludedGroupIds)) {
            $query->whereNotIn('account_group_id', $excludedGroupIds);
        }

        $query->where(function($q) {
            $q->whereNull('type')
              ->orWhereNotIn('type', ['bank', 'cash']);
        });

        // Apply filters directly at the SQL level
        if (!empty($filters['ledger_id'])) {
            $query->where('id', $filters['ledger_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Recursively fetch all child account group IDs.
     */
    protected function getAllDescendantGroups($groupRoots, $allGroups)
    {
        $result = $groupRoots;
        $children = $allGroups->whereIn('parent_id', $groupRoots)->pluck('id')->toArray();
        
        if (!empty($children)) {
            $result = array_merge($result, $this->getAllDescendantGroups($children, $allGroups));
        }
        
        return array_unique($result);
    }
}
