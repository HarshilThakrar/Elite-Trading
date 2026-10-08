<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BalanceSheetService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use Carbon\Carbon;

class BalanceSheetController extends Controller
{
    protected $balanceSheetService;
    protected $financialYearService;

    public function __construct(BalanceSheetService $balanceSheetService, FinancialYearService $financialYearService)
    {
        $this->balanceSheetService = $balanceSheetService;
        $this->financialYearService = $financialYearService;
    }

    public function index(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        
        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        
        if (!$financialYear) {
            $financialYear = $financialYears->first();
        }

        if ($financialYear) {
            $fyId = $financialYear->id;
            $defaultAsOn = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
        } else {
            $defaultAsOn = date('Y-m-d');
        }

        $asOnDate = $request->input('as_on_date', $defaultAsOn);

        // Date Validation
        if ($financialYear) {
            $errors = [];
            $fyStart = $financialYear->start_date->format('Y-m-d');
            $fyEnd = $financialYear->end_date->format('Y-m-d');

            if ($asOnDate < $fyStart) {
                $errors[] = "As On Date ({$asOnDate}) cannot be before Financial Year start ({$fyStart}).";
            }
            if ($asOnDate > $fyEnd) {
                $errors[] = "As On Date ({$asOnDate}) cannot be after Financial Year end ({$fyEnd}).";
            }

            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }
        }

        $filters = [
            'show_zero' => $request->boolean('show_zero', false),
            'view_mode' => $request->input('view_mode', 'grouped'),
        ];

        $report = null;
        if ($financialYear && empty($errors)) {
            $report = $this->balanceSheetService->getReportData($financialYear, $asOnDate, $filters);
        }

        return view('admin.balance-sheet.index', compact(
            'financialYears', 'financialYear',
            'asOnDate', 'filters', 'report'
        ));
    }

    public function getReportData(Request $request)
    {
        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        
        $asOnDate = $request->input('as_on_date');
        
        $filters = [
            'show_zero' => $request->boolean('show_zero', false),
            'view_mode' => $request->input('view_mode', 'grouped'),
        ];

        return [
            'financialYear' => $financialYear,
            'asOnDate' => $asOnDate,
            'filters' => $filters,
            'report' => $this->balanceSheetService->getReportData($financialYear, $asOnDate, $filters)
        ];
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = "balance_sheet_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Type', 'Account Group', 'Ledger', 'Amount']);

            $report = $data['report'];
            
            // Liabilities & Equity
            foreach ($report['liability_rows'] as $row) {
                fputcsv($file, [
                    'Liability/Equity',
                    $row['ledger']->accountGroup->name ?? '-',
                    $row['ledger']->name,
                    $row['amount']
                ]);
            }
            fputcsv($file, ['Liability/Equity', 'P&L', 'Current Year Profit/Loss', $report['current_profit']]);
            
            // Assets
            foreach ($report['asset_rows'] as $row) {
                fputcsv($file, [
                    'Asset',
                    $row['ledger']->accountGroup->name ?? '-',
                    $row['ledger']->name,
                    $row['amount']
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Total Liabilities & Equity', '', '', $report['total_liabilities']]);
            fputcsv($file, ['Total Assets', '', '', $report['total_assets']]);
            
            if (!$report['is_balanced']) {
                fputcsv($file, ['Difference', '', '', abs($report['difference'])]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.balance-sheet.pdf', $data);
            return $pdf->download("balance_sheet.pdf");
        }
        
        return view('admin.balance-sheet.pdf', $data);
    }
}
