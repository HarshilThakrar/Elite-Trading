<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function getTrialBalance($financialYear, $fromDate, $toDate, $filters = [])
    {
        $ledgersQuery = Ledger::with('accountGroup')->where('is_active', true);
        
        if (!empty($filters['account_group_id'])) {
            $groupIds = $this->getAllChildGroupIds($filters['account_group_id']);
            $groupIds[] = $filters['account_group_id'];
            $ledgersQuery->whereIn('account_group_id', array_unique($groupIds));
        }

        if (!empty($filters['ledger_id'])) {
            $ledgersQuery->where('id', $filters['ledger_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $ledgersQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ledger_code', 'like', "%{$search}%");
            });
        }

        $ledgers = $ledgersQuery->get()->keyBy('id');
        $ledgerIds = $ledgers->pluck('id')->toArray();

        // 1. Get Opening Balances up to $fromDate
        $openingBalances = $this->calculateOpeningBalances($ledgerIds, $financialYear, $fromDate);

        // 2. Get Period Movements between $fromDate and $toDate
        $periodMovements = $this->calculatePeriodMovements($ledgerIds, $financialYear, $fromDate, $toDate);

        // 3. Assemble Flat Ledger Rows
        $rows = [];
        $grandTotals = [
            'opening_dr' => 0, 'opening_cr' => 0,
            'period_dr' => 0, 'period_cr' => 0,
            'closing_dr' => 0, 'closing_cr' => 0,
        ];

        $showZero = $filters['show_zero'] ?? false;

        foreach ($ledgers as $ledgerId => $ledger) {
            $ob = $openingBalances[$ledgerId] ?? ['dr' => 0, 'cr' => 0, 'net' => 0];
            $pm = $periodMovements[$ledgerId] ?? ['dr' => 0, 'cr' => 0];

            $closingNet = $ob['net'] + $pm['dr'] - $pm['cr'];
            $closingDr = $closingNet > 0 ? $closingNet : 0;
            $closingCr = $closingNet < 0 ? abs($closingNet) : 0;

            if (!$showZero && $ob['dr'] == 0 && $ob['cr'] == 0 && $pm['dr'] == 0 && $pm['cr'] == 0 && $closingDr == 0 && $closingCr == 0) {
                continue; // Skip zero balance accounts if filter says no
            }

            $rows[$ledgerId] = [
                'ledger' => $ledger,
                'opening_dr' => $ob['dr'],
                'opening_cr' => $ob['cr'],
                'period_dr' => $pm['dr'],
                'period_cr' => $pm['cr'],
                'closing_dr' => $closingDr,
                'closing_cr' => $closingCr,
            ];

            $grandTotals['opening_dr'] += $ob['dr'];
            $grandTotals['opening_cr'] += $ob['cr'];
            $grandTotals['period_dr'] += $pm['dr'];
            $grandTotals['period_cr'] += $pm['cr'];
            $grandTotals['closing_dr'] += $closingDr;
            $grandTotals['closing_cr'] += $closingCr;
        }

        // 4. Build Grouped Hierarchy
        $grouped = [];
        if (($filters['view_mode'] ?? 'detailed') === 'grouped') {
            $grouped = $this->buildGroupedView($rows);
        }

        $grandTotals['difference'] = $grandTotals['closing_dr'] - $grandTotals['closing_cr'];

        return [
            'rows' => $rows,
            'grouped' => $grouped,
            'grand_totals' => $grandTotals
        ];
    }

    private function calculateOpeningBalances($ledgerIds, $financialYear, $fromDate)
    {
        if (empty($ledgerIds) || !$financialYear) {
            return [];
        }

        $ledgers = Ledger::whereIn('id', $ledgerIds)->with(['openingBalances' => function($q) use ($financialYear) {
            $q->where('financial_year_id', $financialYear->id);
        }])->get();

        $fyStart = $financialYear->start_date->format('Y-m-d');
        
        // Sum posted movements from FY start up to fromDate (exclusive of fromDate)
        $movements = collect();
        if ($fromDate > $fyStart) {
            $movements = JournalEntry::select('ledger_id', 
                DB::raw("SUM(CASE WHEN journal_entries.type = 'Dr' THEN journal_entries.amount ELSE 0 END) as period_dr"),
                DB::raw("SUM(CASE WHEN journal_entries.type = 'Cr' THEN journal_entries.amount ELSE 0 END) as period_cr")
            )
            ->join('vouchers', 'vouchers.id', '=', 'journal_entries.voucher_id')
            ->whereIn('ledger_id', $ledgerIds)
            ->where('vouchers.status', 'Posted')
            ->where('vouchers.financial_year_id', $financialYear->id)
            ->where('vouchers.date', '>=', $fyStart)
            ->where('vouchers.date', '<', $fromDate)
            ->groupBy('ledger_id')
            ->get()
            ->keyBy('ledger_id');
        }

        // Single optimized query to check which ledgers have ANY opening balances across all years
        $ledgersWithAnyOb = OpeningBalance::whereIn('ledger_id', $ledgerIds)
            ->pluck('ledger_id')
            ->flip()
            ->toArray();

        $result = [];
        foreach ($ledgers as $ledger) {
            $obAmount = (float) $ledger->opening_balance;
            $obType = $ledger->opening_balance_type;
            
            $fyOpening = $ledger->openingBalances->first();
            if ($fyOpening) {
                $obAmount = (float) $fyOpening->amount;
                $obType = $fyOpening->type;
            } else {
                if (isset($ledgersWithAnyOb[$ledger->id])) {
                    $obAmount = 0;
                    $obType = 'Dr';
                }
            }

            $netOpening = ($obType === 'Dr') ? $obAmount : -$obAmount;
            
            $mov = $movements->get($ledger->id);
            if ($mov) {
                $netOpening += ((float)$mov->period_dr - (float)$mov->period_cr);
            }

            $result[$ledger->id] = [
                'net' => $netOpening,
                'dr' => $netOpening > 0 ? $netOpening : 0,
                'cr' => $netOpening < 0 ? abs($netOpening) : 0,
            ];
        }

        return $result;
    }

    private function calculatePeriodMovements($ledgerIds, $financialYear, $fromDate, $toDate)
    {
        if (empty($ledgerIds) || !$financialYear) {
            return [];
        }

        return JournalEntry::select('ledger_id', 
                DB::raw("SUM(CASE WHEN journal_entries.type = 'Dr' THEN journal_entries.amount ELSE 0 END) as period_dr"),
                DB::raw("SUM(CASE WHEN journal_entries.type = 'Cr' THEN journal_entries.amount ELSE 0 END) as period_cr")
            )
            ->join('vouchers', 'vouchers.id', '=', 'journal_entries.voucher_id')
            ->whereIn('ledger_id', $ledgerIds)
            ->where('vouchers.status', 'Posted')
            ->where('vouchers.financial_year_id', $financialYear->id)
            ->whereBetween('vouchers.date', [$fromDate, $toDate])
            ->groupBy('ledger_id')
            ->get()
            ->keyBy('ledger_id')
            ->map(function ($item) {
                return ['dr' => (float) $item->period_dr, 'cr' => (float) $item->period_cr];
            })
            ->toArray();
    }

    private function buildGroupedView($rows)
    {
        $allGroups = AccountGroup::all()->keyBy('id');
        $tree = [];
        $groupTotals = [];

        // Initialize group totals
        foreach ($allGroups as $group) {
            $groupTotals[$group->id] = [
                'opening_dr' => 0, 'opening_cr' => 0,
                'period_dr' => 0, 'period_cr' => 0,
                'closing_dr' => 0, 'closing_cr' => 0,
                'ledgers' => [],
                'children' => []
            ];
        }

        // Attach ledgers to their direct groups and bubble up totals
        foreach ($rows as $ledgerId => $row) {
            $groupId = $row['ledger']->account_group_id;
            if (isset($groupTotals[$groupId])) {
                $groupTotals[$groupId]['ledgers'][] = $row;
                $this->bubbleUpTotals($groupTotals, $allGroups, $groupId, $row);
            }
        }

        // Build tree with circular reference check
        foreach ($allGroups as $group) {
            if ($group->parent_id && isset($groupTotals[$group->parent_id]) && $group->parent_id != $group->id) {
                $groupTotals[$group->parent_id]['children'][$group->id] = &$groupTotals[$group->id];
            } else {
                $tree[$group->id] = &$groupTotals[$group->id];
            }
        }
        
        // Remove empty groups recursively with visited guard
        $this->pruneEmptyGroups($tree);

        return ['tree' => $tree, 'allGroups' => $allGroups];
    }

    private function bubbleUpTotals(&$groupTotals, $allGroups, $groupId, $row)
    {
        $currentGroupId = $groupId;
        $visited = [];
        while ($currentGroupId && !isset($visited[$currentGroupId])) {
            $visited[$currentGroupId] = true;
            if (isset($groupTotals[$currentGroupId])) {
                $groupTotals[$currentGroupId]['opening_dr'] += $row['opening_dr'];
                $groupTotals[$currentGroupId]['opening_cr'] += $row['opening_cr'];
                $groupTotals[$currentGroupId]['period_dr'] += $row['period_dr'];
                $groupTotals[$currentGroupId]['period_cr'] += $row['period_cr'];
                $groupTotals[$currentGroupId]['closing_dr'] += $row['closing_dr'];
                $groupTotals[$currentGroupId]['closing_cr'] += $row['closing_cr'];
            }
            
            $group = $allGroups->get($currentGroupId);
            $currentGroupId = ($group && $group->parent_id != $currentGroupId) ? $group->parent_id : null;
        }
    }

    private function pruneEmptyGroups(&$tree, &$visited = [])
    {
        foreach ($tree as $id => &$node) {
            if (isset($visited[$id])) {
                unset($tree[$id]);
                continue;
            }
            $visited[$id] = true;
            if (!empty($node['children'])) {
                $this->pruneEmptyGroups($node['children'], $visited);
            }
            
            if (empty($node['ledgers']) && empty($node['children'])) {
                unset($tree[$id]);
            }
        }
    }

    private function getAllChildGroupIds($parentId, &$visited = [])
    {
        if (in_array($parentId, $visited)) {
            return [];
        }
        $visited[] = $parentId;

        $children = AccountGroup::where('parent_id', $parentId)->pluck('id')->toArray();
        $allIds = $children;
        foreach ($children as $childId) {
            if (!in_array($childId, $visited)) {
                $allIds = array_merge($allIds, $this->getAllChildGroupIds($childId, $visited));
            }
        }
        return array_unique($allIds);
    }
}
