<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Ledger;
use App\Services\AccountingEngine;
use App\Services\FinancialYearService;
use App\Services\AccountingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ReceiptVoucherController extends Controller
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
        $query = Voucher::where('type', 'Receipt')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%")
                  ->orWhere('metadata->reference_number', 'like', "%{$search}%");
            });
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('admin.receipt-vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $settings = $this->settingsService->getSettings();
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        $ledgers = $ledgersQuery->get();

        $cashBankLedgers = $ledgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        return view('admin.receipt-vouchers.create', compact('ledgers', 'cashBankLedgers', 'settings'));
    }

    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'receipt_mode' => 'required|in:Cash,Bank,UPI,Cheque,Other',
            'receive_into_id' => 'required|exists:ledgers,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.credit' => 'required|numeric|min:0.01',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $receiveIntoLedger = Ledger::findOrFail($request->receive_into_id);
            $lines = $this->parseAndValidateLines($request->lines, $receiveIntoLedger);
            
            $totalAmount = array_sum(array_column($lines, 'credit'));

            $metadata = [
                'receipt_mode' => $request->receipt_mode,
                'receive_into_id' => $request->receive_into_id,
                'reference_type' => $request->reference_type,
                'reference_number' => $request->reference_number,
                'reference_date' => $request->reference_date,
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-' . time() . '-' . rand(100, 999),
                    'date' => $request->date,
                    'type' => 'Receipt',
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
                return redirect()->route('receipt-vouchers.index')->with('success', 'Receipt Voucher saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Receipt',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatLinesForAccounting($lines, $receiveIntoLedger->id, $totalAmount);
                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                DB::commit();
                return redirect()->route('receipt-vouchers.index')->with('success', 'Receipt Voucher posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Receipt')->with(['entries.ledger', 'financialYear'])->findOrFail($id);
        return view('admin.receipt-vouchers.show', compact('voucher'));
    }

    public function edit($id)
    {
        $voucher = Voucher::where('type', 'Receipt')->where('status', 'Draft')->findOrFail($id);
        $settings = $this->settingsService->getSettings();
        
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        $ledgers = $ledgersQuery->get();

        $cashBankLedgers = $ledgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        return view('admin.receipt-vouchers.edit', compact('voucher', 'ledgers', 'cashBankLedgers', 'settings'));
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::where('type', 'Receipt')->where('status', 'Draft')->findOrFail($id);
        
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'receipt_mode' => 'required|in:Cash,Bank,UPI,Cheque,Other',
            'receive_into_id' => 'required|exists:ledgers,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.credit' => 'required|numeric|min:0.01',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $receiveIntoLedger = Ledger::findOrFail($request->receive_into_id);
            $lines = $this->parseAndValidateLines($request->lines, $receiveIntoLedger);
            
            $totalAmount = array_sum(array_column($lines, 'credit'));

            $metadata = [
                'receipt_mode' => $request->receipt_mode,
                'receive_into_id' => $request->receive_into_id,
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
                return redirect()->route('receipt-vouchers.index')->with('success', 'Receipt Voucher draft updated.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Receipt',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatLinesForAccounting($lines, $receiveIntoLedger->id, $totalAmount);
                $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                $voucher->delete();
                
                DB::commit();
                return redirect()->route('receipt-vouchers.index')->with('success', 'Receipt Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            }

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function post($id)
    {
        $voucher = Voucher::where('type', 'Receipt')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Explicit idempotency check
            if ($voucher->status === 'Posted') {
                throw new Exception("Voucher already posted.");
            }

            if ($voucher->status !== 'Draft' || !$voucher->draft_data) {
                throw new Exception("Invalid voucher state for posting.");
            }

            $finYear = $this->fyService->validatePostingDate($voucher->date);
            $metadata = $voucher->draft_data['metadata'];
            $receiveIntoLedger = Ledger::findOrFail($metadata['receive_into_id']);
            $lines = $this->parseAndValidateLines($voucher->draft_data['lines'], $receiveIntoLedger);
            $totalAmount = array_sum(array_column($lines, 'credit'));

            $voucherData = [
                'date' => $voucher->date,
                'type' => 'Receipt',
                'narration' => $voucher->narration,
                'financial_year_id' => $finYear->id,
                'created_by' => $voucher->created_by,
                'status' => 'Posted',
                'draft_data' => null, // Clear on post
                'metadata' => $metadata
            ];
            
            $entries = $this->formatLinesForAccounting($lines, $receiveIntoLedger->id, $totalAmount);
            
            // AccountingEngine->postVoucher creates a NEW voucher and generates the number. 
            // We must delete the old draft to prevent duplicates.
            $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
            $voucher->delete();
            
            DB::commit();
            return redirect()->route('receipt-vouchers.index')->with('success', 'Receipt Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Receipt')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('receipt-vouchers.index')->with('success', 'Draft Receipt Voucher deleted.');
            } else {
                throw new Exception("Cancellation of posted vouchers requires reversal logic, which is not fully implemented here yet.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function parseAndValidateLines($rawLines, $receiveIntoLedger)
    {
        $validLines = [];
        $totalCr = 0;
        
        $settings = $this->settingsService->getSettings();
        
        $isReceiveIntoCashOrBank = $this->isCashOrBank($receiveIntoLedger);

        foreach ($rawLines as $line) {
            $credit = (float)($line['credit'] ?? 0);
            
            if ($credit <= 0) {
                continue;
            }
            
            $ledger = Ledger::find($line['ledger_id']);
            if (!$ledger) {
                throw new Exception("Invalid Ledger selected.");
            }
            if (!$ledger->is_active && $settings->prevent_inactive_ledger_posting) {
                throw new Exception("Cannot post to inactive ledger: {$ledger->name}");
            }
            if ($ledger->id === $receiveIntoLedger->id) {
                throw new Exception("Cannot select Receiving Source Ledger ({$ledger->name}) as a credit target.");
            }

            // Contra Validation
            if ($isReceiveIntoCashOrBank && $this->isCashOrBank($ledger)) {
                throw new Exception("Cash/Bank transfers must be recorded through Contra Voucher. Invalid Ledger: {$ledger->name}");
            }
            
            $totalCr += $credit;
            
            $validLines[] = [
                'ledger_id' => $line['ledger_id'],
                'credit' => $credit,
                'narration' => $line['narration'] ?? null,
                'ledger_name' => $ledger->name
            ];
        }

        if (count($validLines) < 1) {
            throw new Exception("Receipt Voucher requires at least one valid credit line.");
        }

        return $validLines;
    }

    private function formatLinesForAccounting($lines, $debitLedgerId, $totalDebit)
    {
        $entries = [];
        
        // Single Debit line
        $entries[] = [
            'ledger_id' => $debitLedgerId,
            'type' => 'Dr',
            'amount' => $totalDebit,
            'narration' => null
        ];

        // Multiple Credit lines
        foreach ($lines as $line) {
            $entries[] = [
                'ledger_id' => $line['ledger_id'],
                'type' => 'Cr',
                'amount' => $line['credit'],
                'narration' => $line['narration']
            ];
        }

        return $entries;
    }

    private function isCashOrBank(Ledger $ledger)
    {
        $groupIds = [8, 9]; // Cash-in-Hand, Bank Accounts
        
        $currentGroup = $ledger->accountGroup;
        while ($currentGroup) {
            if (in_array($currentGroup->id, $groupIds)) {
                return true;
            }
            $currentGroup = $currentGroup->parent;
        }
        return false;
    }
}
