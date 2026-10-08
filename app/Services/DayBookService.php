<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Ledger;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class DayBookService
{
    public function getVoucherTypes()
    {
        return Voucher::select('type')->distinct()->orderBy('type')->pluck('type')->toArray();
    }

    public function getLedgers()
    {
        return Ledger::where('is_active', true)->orderBy('name')->get();
    }

    public function getDayBook($financialYear, $fromDate, $toDate, $filters = [], $isExport = false)
    {
        $query = Voucher::with(['entries.ledger'])
            ->where('status', 'Posted')
            ->where('financial_year_id', $financialYear->id)
            ->whereBetween('date', [$fromDate, $toDate]);

        // Filter by Voucher Type
        if (!empty($filters['voucher_type'])) {
            $query->where('type', $filters['voucher_type']);
        }
        
        // Filter by Voucher Number
        if (!empty($filters['voucher_number'])) {
            $query->where('voucher_number', 'like', '%' . $filters['voucher_number'] . '%');
        }

        // Search in Narration or Reference
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('narration', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('voucher_number', 'like', "%{$search}%")
                  ->orWhereHas('entries.ledger', function($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Ledger
        if (!empty($filters['ledger_id'])) {
            $ledgerId = $filters['ledger_id'];
            $query->whereHas('entries', function($q) use ($ledgerId) {
                $q->where('ledger_id', $ledgerId);
            });
            // We do NOT filter the eager load, because we want all legs of the matching voucher.
        }

        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        if ($isExport) {
            $vouchers = $query->get();
        } else {
            $vouchers = $query->paginate(20)->withQueryString();
        }

        // Calculate Totals for the Current View/Page
        $totalDr = 0;
        $totalCr = 0;
        $dailySummary = [];
        $unbalancedCount = 0;

        foreach ($vouchers as $voucher) {
            $vDr = 0;
            $vCr = 0;
            $dateStr = $voucher->date->format('Y-m-d');
            
            if (!isset($dailySummary[$dateStr])) {
                $dailySummary[$dateStr] = ['vouchers' => 0, 'dr' => 0, 'cr' => 0];
            }

            foreach ($voucher->entries as $entry) {
                if ($entry->type === 'Dr') {
                    $vDr += $entry->amount;
                    $totalDr += $entry->amount;
                    $dailySummary[$dateStr]['dr'] += $entry->amount;
                } else {
                    $vCr += $entry->amount;
                    $totalCr += $entry->amount;
                    $dailySummary[$dateStr]['cr'] += $entry->amount;
                }
            }

            $dailySummary[$dateStr]['vouchers'] += 1;
            $voucher->is_balanced = abs($vDr - $vCr) < 0.01;
            if (!$voucher->is_balanced) {
                $unbalancedCount++;
            }
        }

        return [
            'vouchers' => $vouchers,
            'total_dr' => $totalDr,
            'total_cr' => $totalCr,
            'daily_summary' => $dailySummary,
            'is_balanced' => abs($totalDr - $totalCr) < 0.01 && $unbalancedCount == 0,
            'unbalanced_count' => $unbalancedCount
        ];
    }
}
