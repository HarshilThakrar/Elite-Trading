<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    public function getReportData($financialYear, $fromDate, $toDate, $filters = [])
    {
        // 1. Get all groups with nature = Income or Expenses
        $allGroups = AccountGroup::all()->keyBy('id');
        
        $incomeGroupIds = [];
        $expenseGroupIds = [];
        
        foreach ($allGroups as $group) {
            if ($group->nature === 'Income') {
                $incomeGroupIds[] = $group->id;
            } elseif ($group->nature === 'Expenses') {
                $expenseGroupIds[] = $group->id;
            }
        }

        // 2. Fetch all ledgers belonging to these groups
        $ledgersQuery = Ledger::with('accountGroup')
            ->whereIn('account_group_id', array_merge($incomeGroupIds, $expenseGroupIds))
            ->where('is_active', true);

        $ledgers = $ledgersQuery->get()->keyBy('id');
        $ledgerIds = $ledgers->pluck('id')->toArray();

        // 3. Fetch period movements
        $periodMovements = $this->calculatePeriodMovements($ledgerIds, $financialYear, $fromDate, $toDate);

        // 4. Assemble Data
        $incomeRows = [];
        $expenseRows = [];
        
        $totalIncome = 0;
        $totalExpenses = 0;

        $showZero = $filters['show_zero'] ?? false;

        foreach ($ledgers as $ledgerId => $ledger) {
            $pm = $periodMovements[$ledgerId] ?? ['dr' => 0, 'cr' => 0];
            
            $isIncome = in_array($ledger->account_group_id, $incomeGroupIds);
            
            if ($isIncome) {
                // Normal balance is Cr
                $amount = $pm['cr'] - $pm['dr'];
            } else {
                // Normal balance is Dr
                $amount = $pm['dr'] - $pm['cr'];
            }

            if (!$showZero && $amount == 0 && $pm['dr'] == 0 && $pm['cr'] == 0) {
                continue; // Skip zero balance accounts
            }

            $row = [
                'ledger' => $ledger,
                'period_dr' => $pm['dr'],
                'period_cr' => $pm['cr'],
                'amount' => $amount,
                'is_income' => $isIncome
            ];

            if ($isIncome) {
                $incomeRows[$ledgerId] = $row;
                $totalIncome += $amount;
            } else {
                $expenseRows[$ledgerId] = $row;
                $totalExpenses += $amount;
            }
        }

        // 5. Build Grouped Views
        $groupedIncome = [];
        $groupedExpense = [];
        if (($filters['view_mode'] ?? 'grouped') === 'grouped') {
            $groupedIncome = $this->buildGroupedView($incomeRows, $allGroups, $incomeGroupIds);
            $groupedExpense = $this->buildGroupedView($expenseRows, $allGroups, $expenseGroupIds);
        }

        $netProfit = $totalIncome - $totalExpenses;

        return [
            'income_rows' => $incomeRows,
            'expense_rows' => $expenseRows,
            'grouped_income' => $groupedIncome,
            'grouped_expense' => $groupedExpense,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
    }

    private function calculatePeriodMovements($ledgerIds, $financialYear, $fromDate, $toDate)
    {
        if (empty($ledgerIds)) return [];

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

    private function buildGroupedView($rows, $allGroups, $targetGroupIds)
    {
        $tree = [];
        $groupTotals = [];

        // Initialize group totals only for target groups
        foreach ($targetGroupIds as $groupId) {
            $groupTotals[$groupId] = [
                'amount' => 0,
                'ledgers' => [],
                'children' => []
            ];
        }

        // Attach ledgers and bubble up totals
        foreach ($rows as $ledgerId => $row) {
            $groupId = $row['ledger']->account_group_id;
            if (isset($groupTotals[$groupId])) {
                $groupTotals[$groupId]['ledgers'][] = $row;
                $this->bubbleUpTotals($groupTotals, $allGroups, $groupId, $row['amount']);
            }
        }

        // Build tree
        foreach ($targetGroupIds as $groupId) {
            $group = $allGroups->get($groupId);
            if ($group->parent_id && isset($groupTotals[$group->parent_id])) {
                $groupTotals[$group->parent_id]['children'][$group->id] = &$groupTotals[$group->id];
            } else {
                $tree[$group->id] = &$groupTotals[$group->id];
            }
        }
        
        // Remove empty groups recursively
        $this->pruneEmptyGroups($tree);

        return ['tree' => $tree, 'allGroups' => $allGroups];
    }

    private function bubbleUpTotals(&$groupTotals, $allGroups, $groupId, $amount)
    {
        $currentGroupId = $groupId;
        while ($currentGroupId) {
            if (isset($groupTotals[$currentGroupId])) {
                $groupTotals[$currentGroupId]['amount'] += $amount;
            }
            
            $group = $allGroups->get($currentGroupId);
            $currentGroupId = $group ? $group->parent_id : null;
        }
    }

    private function pruneEmptyGroups(&$tree)
    {
        foreach ($tree as $id => &$node) {
            if (!empty($node['children'])) {
                $this->pruneEmptyGroups($node['children']);
            }
            
            if (empty($node['ledgers']) && empty($node['children'])) {
                unset($tree[$id]);
            }
        }
    }
}
