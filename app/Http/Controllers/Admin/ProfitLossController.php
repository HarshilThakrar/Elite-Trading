<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProfitLossService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use Carbon\Carbon;

class ProfitLossController extends Controller
{
    protected $profitLossService;
    protected $financialYearService;

    public function __construct(ProfitLossService $profitLossService, FinancialYearService $financialYearService)
    {
        $this->profitLossService = $profitLossService;
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
            $defaultFrom = $financialYear->start_date->format('Y-m-d');
            $defaultTo = min($financialYear->end_date, Carbon::now())->format('Y-m-d');
        } else {
            $defaultFrom = date('Y-m-d');
            $defaultTo = date('Y-m-d');
        }

        $fromDate = $request->input('from_date', $defaultFrom);
        $toDate = $request->input('to_date', $defaultTo);

        // Date Validation
        if ($financialYear) {
            $errors = [];
            $fyStart = $financialYear->start_date->format('Y-m-d');
            $fyEnd = $financialYear->end_date->format('Y-m-d');

            if ($fromDate < $fyStart) {
                $errors[] = "From Date ({$fromDate}) cannot be before Financial Year start ({$fyStart}).";
            }
            if ($toDate > $fyEnd) {
                $errors[] = "To Date ({$toDate}) cannot be after Financial Year end ({$fyEnd}).";
            }
            if ($fromDate > $toDate) {
                $errors[] = "From Date cannot be after To Date.";
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
            $report = $this->profitLossService->getReportData($financialYear, $fromDate, $toDate, $filters);
        }

        return view('admin.profit-loss.index', compact(
            'financialYears', 'financialYear',
            'fromDate', 'toDate', 'filters', 'report'
        ));
    }

    public function getReportData(Request $request)
    {
        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $filters = [
            'show_zero' => $request->boolean('show_zero', false),
            'view_mode' => $request->input('view_mode', 'grouped'),
        ];

        return [
            'financialYear' => $financialYear,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'filters' => $filters,
            'report' => $this->profitLossService->getReportData($financialYear, $fromDate, $toDate, $filters)
        ];
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = "profit_loss_" . date('Ymd_His') . ".csv";

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
            
            // Income
            foreach ($report['income_rows'] as $row) {
                fputcsv($file, [
                    'Income',
                    $row['ledger']->accountGroup->name ?? '-',
                    $row['ledger']->name,
                    $row['amount']
                ]);
            }
            
            // Expenses
            foreach ($report['expense_rows'] as $row) {
                fputcsv($file, [
                    'Expense',
                    $row['ledger']->accountGroup->name ?? '-',
                    $row['ledger']->name,
                    $row['amount']
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Total Income', '', '', $report['total_income']]);
            fputcsv($file, ['Total Expenses', '', '', $report['total_expenses']]);
            fputcsv($file, ['Net Profit / Loss', '', '', $report['net_profit']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.profit-loss.pdf', $data);
            return $pdf->download("profit_loss.pdf");
        }
        
        return view('admin.profit-loss.pdf', $data);
    }
}
