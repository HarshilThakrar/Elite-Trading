<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CashBookService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use Carbon\Carbon;

class CashBookController extends Controller
{
    protected $cashBookService;
    protected $financialYearService;

    public function __construct(CashBookService $cashBookService, FinancialYearService $financialYearService)
    {
        $this->cashBookService = $cashBookService;
        $this->financialYearService = $financialYearService;
    }

    public function index(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $cashLedgers = $this->cashBookService->getCashLedgers();

        $fyId = $request->input('financial_year_id');
        $ledgerId = $request->input('ledger_id');
        
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
        
        // Ensure dates are within FY
        if ($financialYear) {
            if ($fromDate < $financialYear->start_date->format('Y-m-d')) {
                $fromDate = $financialYear->start_date->format('Y-m-d');
            }
            if ($toDate > $financialYear->end_date->format('Y-m-d')) {
                $toDate = $financialYear->end_date->format('Y-m-d');
            }
        }

        if (!$ledgerId && $cashLedgers->isNotEmpty()) {
            $ledgerId = $cashLedgers->first()->id;
        }

        $filters = [
            'voucher_type' => $request->input('voucher_type'),
            'search' => $request->input('search')
        ];

        $transactions = collect();
        $openingBalance = ['amount' => 0, 'type' => 'Dr', 'signed_amount' => 0];
        $summary = ['receipts' => 0, 'payments' => 0];

        if ($ledgerId && $financialYear) {
            $openingBalance = $this->cashBookService->getOpeningBalance($ledgerId, $financialYear, $fromDate);
            $query = $this->cashBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
            
            $perPage = 50;
            $transactions = $query->paginate($perPage)->withQueryString();
            
            // Calculate starting balance for current page
            $page = $transactions->currentPage();
            $startingSignedBalance = $openingBalance['signed_amount'];
            
            if ($page > 1) {
                // Sum the skipped records
                $skippedQuery = $this->cashBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
                $skippedRecords = $skippedQuery->limit(($page - 1) * $perPage)->get();
                foreach($skippedRecords as $rec) {
                    $startingSignedBalance += ($rec->type === 'Dr' ? $rec->amount : -$rec->amount);
                }
            }

            // Calculate running balance for the current page records and attach Particulars
            $currentBalance = $startingSignedBalance;
            foreach ($transactions as $transaction) {
                if ($transaction->type === 'Dr') {
                    $currentBalance += (float) $transaction->amount;
                } else {
                    $currentBalance -= (float) $transaction->amount;
                }
                
                $transaction->running_balance = abs($currentBalance);
                $transaction->running_balance_type = $currentBalance >= 0 ? 'Dr' : 'Cr';
                
                $transaction->particulars = $this->cashBookService->getParticulars($transaction);
            }

            $summary = $this->cashBookService->getSummary($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
        }

        $closingBalanceSigned = $openingBalance['signed_amount'] + $summary['receipts'] - $summary['payments'];
        $closingBalance = [
            'amount' => abs($closingBalanceSigned),
            'type' => $closingBalanceSigned >= 0 ? 'Dr' : 'Cr'
        ];

        return view('admin.cash-book.index', compact(
            'financialYears', 'cashLedgers', 'financialYear', 'ledgerId', 
            'fromDate', 'toDate', 'transactions', 'openingBalance', 'summary', 'closingBalance'
        ));
    }

    private function getReportData(Request $request)
    {
        $fyId = $request->input('financial_year_id');
        $ledgerId = $request->input('ledger_id');
        
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        if (!$financialYear) {
            $financialYear = FinancialYear::orderBy('start_date', 'desc')->first();
        }

        $fromDate = $request->input('from_date', date('Y-m-d'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        
        $ledger = \App\Models\Ledger::findOrFail($ledgerId);

        $filters = [
            'voucher_type' => $request->input('voucher_type'),
            'search' => $request->input('search')
        ];

        $openingBalance = $this->cashBookService->getOpeningBalance($ledgerId, $financialYear, $fromDate);
        $query = $this->cashBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
        $transactions = $query->get();

        $currentBalance = $openingBalance['signed_amount'];
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'Dr') {
                $currentBalance += (float) $transaction->amount;
            } else {
                $currentBalance -= (float) $transaction->amount;
            }
            $transaction->running_balance = abs($currentBalance);
            $transaction->running_balance_type = $currentBalance >= 0 ? 'Dr' : 'Cr';
            $transaction->particulars = $this->cashBookService->getParticulars($transaction);
        }

        $summary = $this->cashBookService->getSummary($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);

        $closingBalanceSigned = $openingBalance['signed_amount'] + $summary['receipts'] - $summary['payments'];
        $closingBalance = [
            'amount' => abs($closingBalanceSigned),
            'type' => $closingBalanceSigned >= 0 ? 'Dr' : 'Cr'
        ];

        return compact('ledger', 'financialYear', 'fromDate', 'toDate', 'transactions', 'openingBalance', 'summary', 'closingBalance');
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = "cash_book_{$data['ledger']->name}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'Voucher No', 'Type', 'Particulars', 'Reference', 'Receipt (Dr)', 'Payment (Cr)', 'Balance', 'Dr/Cr'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Opening balance
            fputcsv($file, [
                \Carbon\Carbon::parse($data['fromDate'])->format('d-m-Y'),
                'OB',
                'Opening',
                'Opening Balance',
                '-',
                $data['openingBalance']['type'] == 'Dr' ? $data['openingBalance']['amount'] : '',
                $data['openingBalance']['type'] == 'Cr' ? $data['openingBalance']['amount'] : '',
                $data['openingBalance']['amount'],
                $data['openingBalance']['type']
            ]);

            foreach ($data['transactions'] as $t) {
                fputcsv($file, [
                    $t->voucher->date->format('d-m-Y'),
                    $t->voucher->voucher_number,
                    $t->voucher->type,
                    $t->particulars,
                    $t->voucher->reference_id ?? '-',
                    $t->type == 'Dr' ? $t->amount : '',
                    $t->type == 'Cr' ? $t->amount : '',
                    $t->running_balance,
                    $t->running_balance_type
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        
        // Use existing DOMPDF if installed, else fallback to standard view. Assuming laravel-dompdf is installed.
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cash-book.pdf', $data);
            return $pdf->download("cash_book_{$data['ledger']->name}.pdf");
        }
        
        return view('admin.cash-book.pdf', $data);
    }
}
