<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TrialBalanceService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use App\Models\AccountGroup;
use App\Models\Ledger;
use Carbon\Carbon;

class TrialBalanceController extends Controller
{
    protected $trialBalanceService;
    protected $financialYearService;

    public function __construct(TrialBalanceService $trialBalanceService, FinancialYearService $financialYearService)
    {
        $this->trialBalanceService = $trialBalanceService;
        $this->financialYearService = $financialYearService;
    }

    public function index(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $accountGroups = AccountGroup::orderBy('name')->get();
        
        $ledgers = collect();
        if ($request->input('account_group_id')) {
            $ledgers = Ledger::where('account_group_id', $request->input('account_group_id'))->orderBy('name')->get();
        } else {
            $ledgers = Ledger::orderBy('name')->get();
        }

        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        
        if (!$financialYear) {
            $financialYear = $financialYears->first();
        }

        if ($financialYear) {
            $fyId = $financialYear->id;
            $defaultFrom = $financialYear->start_date->format('Y-m-d');
            $defaultTo = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
        } else {
            $defaultFrom = date('Y-m-d');
            $defaultTo = date('Y-m-d');
        }

        $fromDate = $request->input('from_date', $defaultFrom);
        $toDate = $request->input('to_date', $defaultTo);

        // Clamp dates gracefully to Financial Year boundaries to prevent redirect loops or errors
        if ($financialYear) {
            $fyStart = $financialYear->start_date->format('Y-m-d');
            $fyEnd = $financialYear->end_date->format('Y-m-d');

            if ($fromDate < $fyStart || $fromDate > $fyEnd) {
                $fromDate = $fyStart;
            }
            if ($toDate > $fyEnd || $toDate < $fyStart) {
                $toDate = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
                if ($toDate < $fromDate) {
                    $toDate = $fyEnd;
                }
            }
            if ($fromDate > $toDate) {
                $toDate = $fromDate;
            }
        }

        $filters = [
            'account_group_id' => $request->input('account_group_id'),
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
            'show_zero' => $request->boolean('show_zero', false),
            'view_mode' => $request->input('view_mode', 'detailed'),
        ];

        $report = null;
        if ($financialYear) {
            $report = $this->trialBalanceService->getTrialBalance($financialYear, $fromDate, $toDate, $filters);
        }

        return view('admin.trial-balance.index', compact(
            'financialYears', 'accountGroups', 'ledgers', 'financialYear',
            'fromDate', 'toDate', 'filters', 'report'
        ));
    }

    public function getReportData(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        
        if (!$financialYear) {
            $financialYear = $financialYears->first();
        }

        if ($financialYear) {
            $defaultFrom = $financialYear->start_date->format('Y-m-d');
            $defaultTo = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
        } else {
            $defaultFrom = date('Y-m-d');
            $defaultTo = date('Y-m-d');
        }

        $fromDate = $request->input('from_date', $defaultFrom);
        $toDate = $request->input('to_date', $defaultTo);

        if ($financialYear) {
            $fyStart = $financialYear->start_date->format('Y-m-d');
            $fyEnd = $financialYear->end_date->format('Y-m-d');

            if ($fromDate < $fyStart || $fromDate > $fyEnd) {
                $fromDate = $fyStart;
            }
            if ($toDate > $fyEnd || $toDate < $fyStart) {
                $toDate = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
                if ($toDate < $fromDate) {
                    $toDate = $fyEnd;
                }
            }
            if ($fromDate > $toDate) {
                $toDate = $fromDate;
            }
        }
        
        $filters = [
            'account_group_id' => $request->input('account_group_id'),
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
            'show_zero' => $request->boolean('show_zero', false),
            'view_mode' => $request->input('view_mode', 'detailed'),
        ];

        return [
            'financialYear' => $financialYear,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'filters' => $filters,
            'report' => $this->trialBalanceService->getTrialBalance($financialYear, $fromDate, $toDate, $filters)
        ];
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = "trial_balance_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Account Group', 'Ledger', 'Opening Dr', 'Opening Cr', 'Period Dr', 'Period Cr', 'Closing Dr', 'Closing Cr']);

            $rows = $data['report']['rows'] ?? [];
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['ledger']->accountGroup->name ?? '-',
                    $row['ledger']->name,
                    $row['opening_dr'] > 0 ? $row['opening_dr'] : '-',
                    $row['opening_cr'] > 0 ? $row['opening_cr'] : '-',
                    $row['period_dr'] > 0 ? $row['period_dr'] : '-',
                    $row['period_cr'] > 0 ? $row['period_cr'] : '-',
                    $row['closing_dr'] > 0 ? $row['closing_dr'] : '-',
                    $row['closing_cr'] > 0 ? $row['closing_cr'] : '-'
                ]);
            }

            // Grand Totals
            if (!empty($data['report']['grand_totals'])) {
                $gt = $data['report']['grand_totals'];
                fputcsv($file, [
                    'GRAND TOTAL',
                    '',
                    $gt['opening_dr'],
                    $gt['opening_cr'],
                    $gt['period_dr'],
                    $gt['period_cr'],
                    $gt['closing_dr'],
                    $gt['closing_cr']
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.trial-balance.pdf', $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream("trial_balance.pdf");
        }
        
        return view('admin.trial-balance.pdf', $data);
    }
}
