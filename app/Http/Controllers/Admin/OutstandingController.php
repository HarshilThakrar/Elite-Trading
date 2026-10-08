<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OutstandingService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use Carbon\Carbon;

class OutstandingController extends Controller
{
    protected $outstandingService;
    protected $financialYearService;

    public function __construct(OutstandingService $outstandingService, FinancialYearService $financialYearService)
    {
        $this->outstandingService = $outstandingService;
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

        $defaultAsOf = $financialYear ? min($financialYear->end_date, Carbon::now())->format('Y-m-d') : date('Y-m-d');
        $asOfDate = $request->input('as_of_date', $defaultAsOf);

        $type = $request->input('type', 'receivables'); // receivables or payables
        if (!in_array($type, ['receivables', 'payables'])) {
            $type = 'receivables';
        }

        $filters = [
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
        ];

        $report = null;
        if ($financialYear) {
            $report = $this->outstandingService->getOutstandingData($type, $financialYear, $asOfDate, $filters);
        }

        return view('admin.outstanding.index', compact(
            'financialYears', 'financialYear',
            'asOfDate', 'type', 'filters', 'report'
        ));
    }

    public function getReportData(Request $request)
    {
        $fyId = $request->input('financial_year_id');
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        $asOfDate = $request->input('as_of_date', Carbon::now()->format('Y-m-d'));
        $type = $request->input('type', 'receivables');
        
        $filters = [
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
        ];

        return [
            'financialYear' => $financialYear,
            'asOfDate' => $asOfDate,
            'type' => $type,
            'filters' => $filters,
            'report' => $this->outstandingService->getOutstandingData($type, $financialYear, $asOfDate, $filters)
        ];
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $type = ucfirst($data['type']);
        $filename = "outstanding_{$type}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($data) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['Party / Invoice', 'Date', 'Due Date', 'Amount', 'Paid/Adj', 'Outstanding', 'Overdue Days', 'Aging Bucket']);

            $report = $data['report'];
            
            foreach ($report['data'] as $party) {
                // Party Header
                fputcsv($file, [
                    $party['ledger']->name,
                    '',
                    '',
                    '',
                    '',
                    $party['total_outstanding'],
                    '',
                    ''
                ]);
                
                // Invoices
                foreach ($party['invoices'] as $inv) {
                    fputcsv($file, [
                        '  ' . $inv['voucher_number'],
                        $inv['date']->format('Y-m-d'),
                        $inv['due_date']->format('Y-m-d'),
                        $inv['amount'],
                        $inv['paid'],
                        $inv['outstanding'],
                        $inv['days'],
                        str_replace('_', '-', $inv['bucket'])
                    ]);
                }
            }

            fputcsv($file, []);
            fputcsv($file, [
                'TOTAL', '', '', '', '', 
                $report['summary']['total_outstanding'], '', ''
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        $type = ucfirst($data['type']);
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.outstanding.pdf', $data);
            return $pdf->download("outstanding_{$type}.pdf");
        }
        
        return view('admin.outstanding.pdf', $data);
    }
}
