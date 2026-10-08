<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BankBookService;
use App\Services\FinancialYearService;
use App\Models\FinancialYear;
use App\Models\BankStatementTransaction;
use Carbon\Carbon;

class BankBookController extends Controller
{
    protected $bankBookService;
    protected $financialYearService;

    public function __construct(BankBookService $bankBookService, FinancialYearService $financialYearService)
    {
        $this->bankBookService = $bankBookService;
        $this->financialYearService = $financialYearService;
    }

    public function index(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $bankLedgers = $this->bankBookService->getBankLedgers();

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

        if (!$ledgerId) {
            $ledgerId = 'all';
        }

        $filters = [
            'voucher_type' => $request->input('voucher_type'),
            'search' => $request->input('search')
        ];

        $transactions = collect();
        $openingBalance = ['amount' => 0, 'type' => 'Dr', 'signed_amount' => 0];
        $summary = ['receipts' => 0, 'payments' => 0];

        if ($ledgerId && $financialYear) {
            $openingBalance = $this->bankBookService->getOpeningBalance($ledgerId, $financialYear, $fromDate);
            $query = $this->bankBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
            
            $perPage = 50;
            $transactions = $query->paginate($perPage)->withQueryString();
            
            // Calculate starting balance for current page
            $page = $transactions->currentPage();
            $startingSignedBalance = $openingBalance['signed_amount'];
            
            if ($page > 1) {
                // Sum the skipped records
                $skippedQuery = $this->bankBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
                $skippedRecords = $skippedQuery->limit(($page - 1) * $perPage)->get();
                foreach($skippedRecords as $rec) {
                    $startingSignedBalance += ($rec->type === 'Dr' ? $rec->amount : -$rec->amount);
                }
            }

            // Get reconciliation statuses
            $journalEntryIds = $transactions->pluck('id')->toArray();
            $reconStatuses = [];
            if (!empty($journalEntryIds)) {
                $reconStatuses = BankStatementTransaction::whereIn('matched_journal_entry_id', $journalEntryIds)
                    ->pluck('reconciliation_status', 'matched_journal_entry_id')
                    ->toArray();
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
                
                $transaction->particulars = $this->bankBookService->getParticulars($transaction);
                $transaction->recon_status = $reconStatuses[$transaction->id] ?? 'Unreconciled';
            }

            $summary = $this->bankBookService->getSummary($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
        }

        $closingBalanceSigned = $openingBalance['signed_amount'] + $summary['receipts'] - $summary['payments'];
        $closingBalance = [
            'amount' => abs($closingBalanceSigned),
            'type' => $closingBalanceSigned >= 0 ? 'Dr' : 'Cr'
        ];

        return view('admin.bank-book.index', compact(
            'financialYears', 'bankLedgers', 'financialYear', 'ledgerId', 
            'fromDate', 'toDate', 'transactions', 'openingBalance', 'summary', 'closingBalance'
        ));
    }

    private function getReportData(Request $request)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $bankLedgers = $this->bankBookService->getBankLedgers();

        $fyId = $request->input('financial_year_id');
        $ledgerId = $request->input('ledger_id');
        
        $financialYear = $fyId ? FinancialYear::find($fyId) : $this->financialYearService->getCurrentFinancialYear();
        if (!$financialYear) {
            $financialYear = $financialYears->first();
        }

        if (!$ledgerId) {
            $ledgerId = 'all';
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
        
        // Ensure dates are within FY
        if ($financialYear) {
            if ($fromDate < $financialYear->start_date->format('Y-m-d')) {
                $fromDate = $financialYear->start_date->format('Y-m-d');
            }
            if ($toDate > $financialYear->end_date->format('Y-m-d')) {
                $toDate = $financialYear->end_date->format('Y-m-d');
            }
        }

        $ledgerName = 'All Bank Accounts';
        if ($ledgerId && $ledgerId !== 'all') {
            $ledger = \App\Models\Ledger::find($ledgerId);
            $ledgerName = $ledger ? $ledger->name : ($bankLedgers->first()->name ?? 'Bank Account');
            if (!$ledger && $bankLedgers->isNotEmpty()) {
                $ledgerId = $bankLedgers->first()->id;
            }
        }

        $filters = [
            'voucher_type' => $request->input('voucher_type'),
            'search' => $request->input('search')
        ];

        $transactions = collect();
        $openingBalance = ['amount' => 0, 'type' => 'Dr', 'signed_amount' => 0];
        $summary = ['receipts' => 0, 'payments' => 0];

        if ($ledgerId && $financialYear) {
            $openingBalance = $this->bankBookService->getOpeningBalance($ledgerId, $financialYear, $fromDate);
            $query = $this->bankBookService->getTransactionsQuery($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
            $transactions = $query->get();

            $journalEntryIds = $transactions->pluck('id')->toArray();
            $reconStatuses = [];
            if (!empty($journalEntryIds)) {
                $reconStatuses = BankStatementTransaction::whereIn('matched_journal_entry_id', $journalEntryIds)
                    ->pluck('reconciliation_status', 'matched_journal_entry_id')
                    ->toArray();
            }

            $currentBalance = $openingBalance['signed_amount'];
            foreach ($transactions as $transaction) {
                if ($transaction->type === 'Dr') {
                    $currentBalance += (float) $transaction->amount;
                } else {
                    $currentBalance -= (float) $transaction->amount;
                }
                $transaction->running_balance = abs($currentBalance);
                $transaction->running_balance_type = $currentBalance >= 0 ? 'Dr' : 'Cr';
                $transaction->particulars = $this->bankBookService->getParticulars($transaction);
                $transaction->recon_status = $reconStatuses[$transaction->id] ?? 'Unreconciled';
            }

            $summary = $this->bankBookService->getSummary($ledgerId, $financialYear->id, $fromDate, $toDate, $filters);
        }

        $closingBalanceSigned = $openingBalance['signed_amount'] + $summary['receipts'] - $summary['payments'];
        $closingBalance = [
            'amount' => abs($closingBalanceSigned),
            'type' => $closingBalanceSigned >= 0 ? 'Dr' : 'Cr'
        ];

        return compact('ledgerName', 'ledgerId', 'financialYear', 'fromDate', 'toDate', 'transactions', 'openingBalance', 'summary', 'closingBalance');
    }

    public function export(Request $request)
    {
        $data = $this->getReportData($request);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', str_replace(' ', '_', strtolower($data['ledgerName'])));
        $filename = "bank_book_" . $safeName . "_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'Voucher No', 'Type', 'Particulars', 'Reference', 'Receipt (Dr)', 'Payment (Cr)', 'Balance', 'Dr/Cr', 'Recon Status'];
        if ($data['ledgerId'] === 'all') {
            array_splice($columns, 4, 0, 'Bank Ledger');
        }

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, $columns);
            
            // Opening balance row
            $obRow = [
                \Carbon\Carbon::parse($data['fromDate'])->format('d-m-Y'),
                'OB',
                'Opening',
                'Opening Balance b/f'
            ];
            if ($data['ledgerId'] === 'all') $obRow[] = '-';
            
            $obRow = array_merge($obRow, [
                '-',
                $data['openingBalance']['type'] == 'Dr' ? $data['openingBalance']['amount'] : '',
                $data['openingBalance']['type'] == 'Cr' ? $data['openingBalance']['amount'] : '',
                $data['openingBalance']['amount'],
                $data['openingBalance']['type'],
                '-'
            ]);
            fputcsv($file, $obRow);

            foreach ($data['transactions'] as $t) {
                $row = [
                    $t->voucher->date->format('d-m-Y'),
                    $t->voucher->voucher_number,
                    $t->voucher->type,
                    $t->particulars
                ];
                if ($data['ledgerId'] === 'all') {
                    $row[] = $t->ledger->name ?? '-';
                }
                
                $row = array_merge($row, [
                    $t->voucher->reference_id ?? '-',
                    $t->type == 'Dr' ? $t->amount : '',
                    $t->type == 'Cr' ? $t->amount : '',
                    $t->running_balance,
                    $t->running_balance_type,
                    $t->recon_status
                ]);
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', str_replace(' ', '_', strtolower($data['ledgerName'])));
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.bank-book.pdf', $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream("bank_book_{$safeName}.pdf");
        }
        
        return view('admin.bank-book.pdf', $data);
    }
}
