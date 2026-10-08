<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DayBookService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use Carbon\Carbon;

class DayBookController extends Controller
{
    protected $dayBookService;
    protected $financialYearService;

    public function __construct(DayBookService $dayBookService, FinancialYearService $financialYearService)
    {
        $this->dayBookService = $dayBookService;
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
            $now = Carbon::now();
            if ($now < $financialYear->start_date || $now > $financialYear->end_date) {
                $defaultTo = $financialYear->end_date->format('Y-m-d');
            } else {
                $defaultTo = $now->format('Y-m-d');
            }
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
            'voucher_type' => $request->input('voucher_type'),
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
            'voucher_number' => $request->input('voucher_number'),
        ];

        $report = null;
        if ($financialYear && empty($errors)) {
            $report = $this->dayBookService->getDayBook($financialYear, $fromDate, $toDate, $filters, false);
        }

        $voucherTypes = $this->dayBookService->getVoucherTypes();
        $ledgers = $this->dayBookService->getLedgers();

        return view('admin.day-book.index', compact(
            'financialYears', 'financialYear',
            'fromDate', 'toDate', 'filters', 'report',
            'voucherTypes', 'ledgers'
        ));
    }

    public function getReportData(Request $request, $isExport = false)
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
            $now = Carbon::now();
            if ($now < $financialYear->start_date || $now > $financialYear->end_date) {
                $defaultTo = $financialYear->end_date->format('Y-m-d');
            } else {
                $defaultTo = $now->format('Y-m-d');
            }
        } else {
            $defaultFrom = date('Y-m-d');
            $defaultTo = date('Y-m-d');
        }

        $fromDate = $request->input('from_date', $defaultFrom);
        $toDate = $request->input('to_date', $defaultTo);

        // Ensure dates are within FY
        if ($financialYear) {
            if ($fromDate < $financialYear->start_date->format('Y-m-d')) {
                $fromDate = $financialYear->start_date->format('Y-m-d');
            }
            if ($toDate > $financialYear->end_date->format('Y-m-d')) {
                $toDate = $financialYear->end_date->format('Y-m-d');
            }
        }

        $filters = [
            'voucher_type' => $request->input('voucher_type'),
            'ledger_id' => $request->input('ledger_id'),
            'search' => $request->input('search'),
            'voucher_number' => $request->input('voucher_number'),
        ];

        return [
            'financialYear' => $financialYear,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'filters' => $filters,
            'report' => $this->dayBookService->getDayBook($financialYear, $fromDate, $toDate, $filters, $isExport)
        ];
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request, true);
        $filename = "day_book_" . date('Ymd_His') . ".csv";

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
            fputcsv($file, ['Date', 'Voucher Number', 'Type', 'Particulars', 'Debit', 'Credit', 'Narration', 'Reference']);

            $report = $data['report'];
            
            foreach ($report['vouchers'] as $voucher) {
                // Header row for Voucher
                fputcsv($file, [
                    $voucher->date->format('Y-m-d'),
                    $voucher->voucher_number,
                    $voucher->type,
                    '', // Particulars column left blank for header
                    '',
                    '',
                    $voucher->narration,
                    $voucher->reference_number
                ]);
                
                // Journal entries
                foreach ($voucher->entries as $entry) {
                    $dr = $entry->type === 'Dr' ? $entry->amount : '';
                    $cr = $entry->type === 'Cr' ? $entry->amount : '';
                    fputcsv($file, [
                        '',
                        '',
                        '',
                        $entry->ledger ? $entry->ledger->name : '-',
                        $dr,
                        $cr,
                        '',
                        ''
                    ]);
                }
            }

            fputcsv($file, []);
            fputcsv($file, ['Total', '', '', '', $report['total_dr'], $report['total_cr'], '', '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request, true); // true for all records unpaginated
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.day-book.pdf', $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream("day_book_" . date('Ymd_His') . ".pdf");
        }
        
        return view('admin.day-book.pdf', $data);
    }
}
