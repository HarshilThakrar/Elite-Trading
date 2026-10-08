<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FinancialYearService;
use App\Models\Voucher;
use App\Models\Ledger;
use App\Models\JournalEntry;
use App\Models\BankStatementTransaction;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard2Controller extends Controller
{
    public function index(Request $request, FinancialYearService $fyService)
    {
        $currentFy = $fyService->getCurrentFinancialYear();
        $selectedFyId = $request->get('financial_year_id', $currentFy ? $currentFy->id : null);
        $selectedFy = FinancialYear::find($selectedFyId);
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();

        if (!$selectedFy) {
            $totalSales = $totalPurchases = $totalReceipts = $totalPayments = $bankBalance = $cashBalance = $unreconciledBank = 0;
            $months = []; $incomeData = []; $expenseData = []; $topLedgers = [];
            $arBalance = $arCount = $apBalance = $apCount = 0;
            $salesCount = $purchaseCount = $paymentCount = $receiptCount = $contraCount = $journalCount = 0;
            $unreconciledTxns = $unreconciledAmt = 0;
            $lastReconciledOn = 'N/A';
            $recentVouchers = collect();

            return view('admin.dashboard2.index', compact(
                'financialYears', 'selectedFyId', 'selectedFy',
                'totalSales', 'totalPurchases', 'totalReceipts', 'totalPayments', 'bankBalance',
                'cashBalance', 'unreconciledBank',
                'months', 'incomeData', 'expenseData',
                'topLedgers',
                'arBalance', 'arCount', 'apBalance', 'apCount',
                'salesCount', 'purchaseCount', 'paymentCount', 'receiptCount', 'contraCount', 'journalCount',
                'unreconciledTxns', 'unreconciledAmt', 'lastReconciledOn',
                'recentVouchers'
            ));
        }

        // Voucher base query for the selected financial year
        $voucherQuery = Voucher::where('financial_year_id', $selectedFyId)->where('status', 'Posted');

        // 1. Top Summary Cards
        $totalSales = (clone $voucherQuery)->where('type', 'Sales')->withSum(['entries as total_amount' => function($q) {
            $q->where('type', 'Cr')->whereHas('ledger.accountGroup', function($q2) {
                $q2->where('name', 'Sales Accounts');
            });
        }], 'amount')->get()->sum('total_amount');

        $totalPurchases = (clone $voucherQuery)->where('type', 'Purchase')->withSum(['entries as total_amount' => function($q) {
            $q->where('type', 'Dr')->whereHas('ledger.accountGroup', function($q2) {
                $q2->where('name', 'Purchase Accounts');
            });
        }], 'amount')->get()->sum('total_amount');

        $totalReceipts = (clone $voucherQuery)->where('type', 'Receipt')->withSum(['entries as total_amount' => function($q) {
            $q->where('type', 'Dr')->whereHas('ledger.accountGroup', function($q2) {
                $q2->whereIn('name', ['Cash-in-hand', 'Bank Accounts']);
            });
        }], 'amount')->get()->sum('total_amount');

        $totalPayments = (clone $voucherQuery)->where('type', 'Payment')->withSum(['entries as total_amount' => function($q) {
            $q->where('type', 'Cr')->whereHas('ledger.accountGroup', function($q2) {
                $q2->whereIn('name', ['Cash-in-hand', 'Bank Accounts']);
            });
        }], 'amount')->get()->sum('total_amount');

        // Bank Balance
        $bankBalance = 0;
        $bankLedgers = Ledger::whereHas('accountGroup', function($q) {
            $q->where('name', 'Bank Accounts');
        })->get();
        $engine = app(\App\Services\AccountingEngine::class);
        foreach ($bankLedgers as $ledger) {
            $bal = $engine->getLedgerBalance($ledger->id, $selectedFyId);
            $bankBalance += ($bal['type'] == 'Dr' ? $bal['balance'] : -$bal['balance']);
        }

        // 2. Cash & Bank Position
        $cashBalance = 0;
        $cashLedgers = Ledger::whereHas('accountGroup', function($q) {
            $q->where('name', 'Cash-in-hand');
        })->get();
        foreach ($cashLedgers as $ledger) {
            $bal = $engine->getLedgerBalance($ledger->id, $selectedFyId);
            $cashBalance += ($bal['type'] == 'Dr' ? $bal['balance'] : -$bal['balance']);
        }

        $unreconciledBank = BankStatementTransaction::where('reconciliation_status', 'Unreconciled')->sum(DB::raw('credit_amount - debit_amount'));

        // 3. Income vs Expense (Monthly)
        $months = [];
        $incomeData = [];
        $expenseData = [];
        $start = Carbon::parse($selectedFy->start_date);
        $end = Carbon::parse($selectedFy->end_date);
        while ($start <= $end) {
            $monthStr = $start->format('M');
            $months[] = $monthStr;
            $monthStart = $start->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $start->copy()->endOfMonth()->format('Y-m-d');

            // Income
            $inc = JournalEntry::where('type', 'Cr')
                ->whereHas('ledger.accountGroup', function($q) {
                    $q->whereIn('name', ['Direct Incomes', 'Indirect Incomes', 'Sales Accounts']);
                })
                ->whereHas('voucher', function($q) use ($selectedFyId, $monthStart, $monthEnd) {
                    $q->where('financial_year_id', $selectedFyId)
                      ->where('status', 'Posted')
                      ->whereBetween('date', [$monthStart, $monthEnd]);
                })->sum('amount');
            $incomeData[] = $inc;

            // Expense
            $exp = JournalEntry::where('type', 'Dr')
                ->whereHas('ledger.accountGroup', function($q) {
                    $q->whereIn('name', ['Direct Expenses', 'Indirect Expenses', 'Purchase Accounts']);
                })
                ->whereHas('voucher', function($q) use ($selectedFyId, $monthStart, $monthEnd) {
                    $q->where('financial_year_id', $selectedFyId)
                      ->where('status', 'Posted')
                      ->whereBetween('date', [$monthStart, $monthEnd]);
                })->sum('amount');
            $expenseData[] = $exp;

            $start->addMonth();
        }

        // 4. Top 5 Ledgers by Balance
        $topLedgers = [];
        // A naive approach for top ledgers, avoiding N+1 if possible, but since we need engine logic, we'll fetch general ledgers
        $generalLedgers = Ledger::where('is_active', true)->whereHas('accountGroup', function($q) {
            $q->whereNotIn('name', ['Cash-in-hand', 'Bank Accounts']);
        })->get();
        foreach ($generalLedgers as $l) {
            $bal = $engine->getLedgerBalance($l->id, $selectedFyId);
            if ($bal['balance'] > 0) {
                $topLedgers[] = [
                    'name' => $l->name,
                    'balance' => $bal['balance'],
                    'type' => $bal['type']
                ];
            }
        }
        usort($topLedgers, function($a, $b) { return $b['balance'] <=> $a['balance']; });
        $topLedgers = array_slice($topLedgers, 0, 5);

        // 5. Outstanding Summary
        $arBalance = 0;
        $arCount = 0;
        $debtors = Ledger::whereHas('accountGroup', function($q) { $q->where('name', 'Sundry Debtors'); })->get();
        foreach ($debtors as $l) {
            $bal = $engine->getLedgerBalance($l->id, $selectedFyId);
            if ($bal['balance'] > 0 && $bal['type'] == 'Dr') {
                $arBalance += $bal['balance'];
                $arCount++;
            }
        }

        $apBalance = 0;
        $apCount = 0;
        $creditors = Ledger::whereHas('accountGroup', function($q) { $q->where('name', 'Sundry Creditors'); })->get();
        foreach ($creditors as $l) {
            $bal = $engine->getLedgerBalance($l->id, $selectedFyId);
            if ($bal['balance'] > 0 && $bal['type'] == 'Cr') {
                $apBalance += $bal['balance'];
                $apCount++;
            }
        }

        // 6. Voucher Summary
        $voucherCounts = Voucher::where('financial_year_id', $selectedFyId)
            ->where('status', 'Posted')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
            
        $salesCount = $voucherCounts['Sales'] ?? 0;
        $purchaseCount = $voucherCounts['Purchase'] ?? 0;
        $paymentCount = $voucherCounts['Payment'] ?? 0;
        $receiptCount = $voucherCounts['Receipt'] ?? 0;
        $contraCount = $voucherCounts['Contra'] ?? 0;
        $journalCount = $voucherCounts['Journal'] ?? 0;

        // 7. Bank Reconciliation
        $unreconciledTxns = BankStatementTransaction::where('reconciliation_status', 'Unreconciled')->count();
        $unreconciledAmt = abs($unreconciledBank);
        $lastReconciled = BankStatementTransaction::where('reconciliation_status', 'Reconciled')->orderBy('updated_at', 'desc')->first();
        $lastReconciledOn = $lastReconciled ? $lastReconciled->updated_at->format('d-M-Y') : 'N/A';

        // 8. Recent Vouchers
        $recentVouchers = Voucher::where('financial_year_id', $selectedFyId)
            ->where('status', 'Posted')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard2.index', compact(
            'financialYears', 'selectedFyId', 'selectedFy',
            'totalSales', 'totalPurchases', 'totalReceipts', 'totalPayments', 'bankBalance',
            'cashBalance', 'unreconciledBank',
            'months', 'incomeData', 'expenseData',
            'topLedgers',
            'arBalance', 'arCount', 'apBalance', 'apCount',
            'salesCount', 'purchaseCount', 'paymentCount', 'receiptCount', 'contraCount', 'journalCount',
            'unreconciledTxns', 'unreconciledAmt', 'lastReconciledOn',
            'recentVouchers'
        ));
    }
}
