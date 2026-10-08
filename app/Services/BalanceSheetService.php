<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    protected $profitLossService;

    public function __construct(ProfitLossService $profitLossService)
    {
        $this->profitLossService = $profitLossService;
    }

    public function getReportData($financialYear, $asOnDate, $filters = [])
    {
        $fromDate = $financialYear->start_date->format('Y-m-d');
        
        // 1. Get all groups with nature = Assets, Liabilities, Capital
        $allGroups = AccountGroup::all()->keyBy('id');
        
        $assetGroupIds = [];
        $liabilityGroupIds = [];
        
        foreach ($allGroups as $group) {
            if ($group->nature === 'Assets') {
                $assetGroupIds[] = $group->id;
            } elseif (in_array($group->nature, ['Liabilities', 'Capital'])) {
                $liabilityGroupIds[] = $group->id;
            }
        }

        // 2. Fetch all ledgers belonging to these groups
        $ledgersQuery = Ledger::with(['accountGroup', 'openingBalances' => function($q) use ($financialYear) {
                $q->where('financial_year_id', $financialYear->id);
            }])
            ->whereIn('account_group_id', array_merge($assetGroupIds, $liabilityGroupIds))
            ->where('is_active', true);

        $ledgers = $ledgersQuery->get()->keyBy('id');
        $ledgerIds = $ledgers->pluck('id')->toArray();

        // 3. Fetch period movements
        $periodMovements = $this->calculatePeriodMovements($ledgerIds, $financialYear, $fromDate, $asOnDate);

        // 4. Assemble Data
        $assetRows = [];
        $liabilityRows = [];
        
        $totalAssets = 0;
        $totalLiabilities = 0;

        $showZero = $filters['show_zero'] ?? false;

        foreach ($ledgers as $ledgerId => $ledger) {
            $pm = $periodMovements[$ledgerId] ?? ['dr' => 0, 'cr' => 0];
            
            // Opening Balance
            $obAmount = (float) $ledger->opening_balance;
            $obType = $ledger->opening_balance_type;
            
            $fyOpening = $ledger->openingBalances->first();
            if ($fyOpening) {
                $obAmount = (float) $fyOpening->amount;
                $obType = $fyOpening->type;
            } elseif ($ledger->openingBalances()->exists()) {
                $obAmount = 0;
                $obType = 'Dr';
            }

            $netOpening = ($obType === 'Dr') ? $obAmount : -$obAmount;
            $closingNet = $netOpening + $pm['dr'] - $pm['cr'];

            $isAsset = in_array($ledger->account_group_id, $assetGroupIds);
            
            if ($isAsset) {
                // Normal balance is Dr
                $amount = $closingNet;
            } else {
                // Normal balance is Cr
                $amount = -$closingNet;
            }

            if (!$showZero && $amount == 0 && $pm['dr'] == 0 && $pm['cr'] == 0 && $obAmount == 0) {
                continue; // Skip zero balance accounts
            }

            $row = [
                'ledger' => $ledger,
                'closing_net' => $closingNet,
                'amount' => $amount,
                'is_asset' => $isAsset
            ];

            if ($isAsset) {
                $assetRows[$ledgerId] = $row;
                $totalAssets += $amount;
            } else {
                $liabilityRows[$ledgerId] = $row;
                $totalLiabilities += $amount;
            }
        }

        // 5. Get Profit Loss for the current period
        $plReport = $this->profitLossService->getReportData($financialYear, $fromDate, $asOnDate, ['show_zero' => true]);
        $currentProfit = $plReport['net_profit'];

        $totalLiabilities += $currentProfit;

        // 6. Build Grouped Views
        $groupedAssets = [];
        $groupedLiabilities = [];
        if (($filters['view_mode'] ?? 'grouped') === 'grouped') {
            $groupedAssets = $this->buildGroupedView($assetRows, $allGroups, $assetGroupIds);
            $groupedLiabilities = $this->buildGroupedView($liabilityRows, $allGroups, $liabilityGroupIds);
        }

        $difference = $totalAssets - $totalLiabilities;

        return [
            'asset_rows' => $assetRows,
            'liability_rows' => $liabilityRows,
            'grouped_assets' => $groupedAssets,
            'grouped_liabilities' => $groupedLiabilities,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'current_profit' => $currentProfit,
            'difference' => $difference,
            'is_balanced' => abs($difference) < 0.01
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
