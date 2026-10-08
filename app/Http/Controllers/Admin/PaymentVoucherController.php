<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Services\AccountingEngine;
use App\Services\FinancialYearService;
use App\Services\AccountingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentVoucherController extends Controller
{
    protected $accountingEngine;
    protected $fyService;
    protected $settingsService;

    public function __construct(
        AccountingEngine $accountingEngine,
        FinancialYearService $fyService,
        AccountingSettingsService $settingsService
    ) {
        $this->accountingEngine = $accountingEngine;
        $this->fyService = $fyService;
        $this->settingsService = $settingsService;
    }

    public function index(Request $request)
    {
        $query = Voucher::where('type', 'Payment')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%")
                  ->orWhere('metadata->reference_number', 'like', "%{$search}%");
            });
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('admin.payment-vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $settings = $this->settingsService->getSettings();
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        $ledgers = $ledgersQuery->get();

        // Pass bank/cash ledgers separately for easy selection in Payment Source
        $cashBankLedgers = $ledgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        // Safe fallback if account group IDs differ
        if ($cashBankLedgers->isEmpty()) {
            $cashBankLedgers = $ledgers;
        }

        return view('admin.payment-vouchers.create', compact('ledgers', 'cashBankLedgers', 'settings'));
    }

    public function store(Request $request)
    {
        if (empty($request->reference_date)) {
            $request->merge(['reference_date' => null]);
        }

        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'payment_mode' => 'required|in:Cash,Bank,UPI,Cheque,Other',
            'payment_from_id' => 'required|exists:ledgers,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.debit' => 'required|numeric|min:0.01',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            
            $paymentSourceLedger = Ledger::findOrFail($request->payment_from_id);
            $lines = $this->parseAndValidateLines($request->lines, $paymentSourceLedger);
            
            // Total debit equals total credit (the payment source)
            $totalAmount = array_sum(array_column($lines, 'debit'));

            $metadata = [
                'payment_mode' => $request->payment_mode,
                'payment_from_id' => $request->payment_from_id,
                'reference_type' => $request->reference_type,
                'reference_number' => $request->reference_number,
                'reference_date' => $request->reference_date,
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-' . time() . '-' . rand(100, 999),
                    'date' => $request->date,
                    'type' => 'Payment',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'draft_data' => [
                        'lines' => $lines,
                        'metadata' => $metadata
                    ]
                ]);
                
                DB::commit();
                return redirect()->route('payment-vouchers.index')->with('success', 'Payment Voucher saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Payment',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatLinesForAccounting($lines, $paymentSourceLedger->id, $totalAmount);
                
                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                DB::commit();
                return redirect()->route('payment-vouchers.index')->with('success', 'Payment Voucher posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Payment')->with(['entries.ledger', 'financialYear'])->findOrFail($id);
        return view('admin.payment-vouchers.show', compact('voucher'));
    }

    public function edit($id)
    {
        $voucher = Voucher::where('type', 'Payment')->where('status', 'Draft')->findOrFail($id);
        $settings = $this->settingsService->getSettings();
        
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        $ledgers = $ledgersQuery->get();

        $cashBankLedgers = $ledgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        return view('admin.payment-vouchers.edit', compact('voucher', 'ledgers', 'cashBankLedgers', 'settings'));
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::where('type', 'Payment')->where('status', 'Draft')->findOrFail($id);
        
        if (empty($request->reference_date)) {
            $request->merge(['reference_date' => null]);
        }

        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'payment_mode' => 'required|in:Cash,Bank,UPI,Cheque,Other',
            'payment_from_id' => 'required|exists:ledgers,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.debit' => 'required|numeric|min:0.01',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $paymentSourceLedger = Ledger::findOrFail($request->payment_from_id);
            $lines = $this->parseAndValidateLines($request->lines, $paymentSourceLedger);
            
            $totalAmount = array_sum(array_column($lines, 'debit'));

            $metadata = [
                'payment_mode' => $request->payment_mode,
                'payment_from_id' => $request->payment_from_id,
                'reference_type' => $request->reference_type,
                'reference_number' => $request->reference_number,
                'reference_date' => $request->reference_date,
            ];

            if ($request->action === 'draft') {
                $voucher->update([
                    'date' => $request->date,
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'draft_data' => [
                        'lines' => $lines,
                        'metadata' => $metadata
                    ]
                ]);
                DB::commit();
                return redirect()->route('payment-vouchers.index')->with('success', 'Payment Voucher draft updated.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Payment',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatLinesForAccounting($lines, $paymentSourceLedger->id, $totalAmount);
                
                $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                $voucher->delete(); // Remove old draft
                
                DB::commit();
                return redirect()->route('payment-vouchers.index')->with('success', 'Payment Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            }

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Payment')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('payment-vouchers.index')->with('success', 'Draft Payment Voucher deleted.');
            } else {
                throw new Exception("Cancellation of posted vouchers requires reversal logic, which is not fully implemented here yet.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function parseAndValidateLines($rawLines, $paymentSourceLedger)
    {
        $validLines = [];
        $totalDr = 0;
        
        $settings = $this->settingsService->getSettings();
        
        $isPaymentSourceCashOrBank = $this->isCashOrBank($paymentSourceLedger);

        foreach ($rawLines as $line) {
            $debit = (float)($line['debit'] ?? 0);
            
            if ($debit <= 0) {
                continue; // Skip zero/negative lines
            }
            
            $ledger = Ledger::find($line['ledger_id']);
            if (!$ledger) {
                throw new Exception("Invalid Ledger selected.");
            }
            if (!$ledger->is_active && $settings->prevent_inactive_ledger_posting) {
                throw new Exception("Cannot post to inactive ledger: {$ledger->name}");
            }
            if ($ledger->id === $paymentSourceLedger->id) {
                throw new Exception("Cannot select Payment Source Ledger ({$ledger->name}) as a debit target.");
            }

            // Contra Validation
            if ($isPaymentSourceCashOrBank && $this->isCashOrBank($ledger)) {
                throw new Exception("Transfers between Cash and Bank belong in Contra Voucher, not Payment Voucher. Invalid Ledger: {$ledger->name}");
            }
            
            $totalDr += $debit;
            
            $validLines[] = [
                'ledger_id' => $line['ledger_id'],
                'debit' => $debit,
                'narration' => $line['narration'] ?? null,
                'ledger_name' => $ledger->name
            ];
        }

        if (count($validLines) < 1) {
            throw new Exception("Payment Voucher requires at least one valid debit line.");
        }

        return $validLines;
    }

    private function formatLinesForAccounting($lines, $creditLedgerId, $totalCredit)
    {
        $entries = [];
        
        // Add multiple Debit lines
        foreach ($lines as $line) {
            $entries[] = [
                'ledger_id' => $line['ledger_id'],
                'type' => 'Dr',
                'amount' => $line['debit'],
                'narration' => $line['narration']
            ];
        }

        // Add single Credit line
        $entries[] = [
            'ledger_id' => $creditLedgerId,
            'type' => 'Cr',
            'amount' => $totalCredit,
            'narration' => null
        ];

        return $entries;
    }

    /**
     * Helper to check if a ledger belongs to Cash-in-Hand or Bank Accounts groups
     */
    private function isCashOrBank(Ledger $ledger)
    {
        $groupIds = [8, 9]; // Cash-in-Hand, Bank Accounts
        $groupNames = ['cash-in-hand', 'bank accounts', 'cash in hand', 'bank account', 'bank od a/c', 'bank occ a/c'];
        
        $currentGroup = $ledger->accountGroup;
        while ($currentGroup) {
            if (in_array($currentGroup->id, $groupIds) || in_array(strtolower($currentGroup->name), $groupNames)) {
                return true;
            }
            $currentGroup = $currentGroup->parent;
        }
        return false;
    }
}
