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

class ContraVoucherController extends Controller
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
        $query = Voucher::where('type', 'Contra')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%")
                  ->orWhere('metadata->reference_number', 'like', "%{$search}%");
            });
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('admin.contra-vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $settings = $this->settingsService->getSettings();
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        $allLedgers = $ledgersQuery->get();

        $cashBankLedgers = $allLedgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        return view('admin.contra-vouchers.create', compact('cashBankLedgers', 'settings'));
    }

    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'transfer_mode' => 'required|in:Bank Transfer,Cash Deposit,Cash Withdrawal,Internal Transfer,Cheque',
            'source_ledger_id' => 'required|exists:ledgers,id|different:destination_ledger_id',
            'destination_ledger_id' => 'required|exists:ledgers,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules, [
            'source_ledger_id.different' => 'Source and Destination ledgers cannot be the same.'
        ]);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $sourceLedger = Ledger::findOrFail($request->source_ledger_id);
            $destinationLedger = Ledger::findOrFail($request->destination_ledger_id);
            
            $this->validateCashBankClassification($sourceLedger, $destinationLedger);

            $metadata = [
                'transfer_mode' => $request->transfer_mode,
                'source_ledger_id' => $request->source_ledger_id,
                'destination_ledger_id' => $request->destination_ledger_id,
                'amount' => $request->amount,
                'reference_number' => $request->reference_number,
                'reference_date' => $request->reference_date,
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-' . time() . '-' . rand(100, 999),
                    'date' => $request->date,
                    'type' => 'Contra',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'draft_data' => [
                        'metadata' => $metadata
                    ]
                ]);
                
                DB::commit();
                return redirect()->route('contra-vouchers.index')->with('success', 'Contra Voucher saved as Draft.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Contra',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatEntries($sourceLedger->id, $destinationLedger->id, $request->amount);
                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                DB::commit();
                return redirect()->route('contra-vouchers.index')->with('success', 'Contra Voucher posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Contra')->with(['entries.ledger', 'financialYear'])->findOrFail($id);
        return view('admin.contra-vouchers.show', compact('voucher'));
    }

    public function edit($id)
    {
        $voucher = Voucher::where('type', 'Contra')->where('status', 'Draft')->findOrFail($id);
        $settings = $this->settingsService->getSettings();
        
        $allLedgers = Ledger::where('is_active', true)->orderBy('name')->get();

        $cashBankLedgers = $allLedgers->filter(function($ledger) {
            return $this->isCashOrBank($ledger);
        });

        return view('admin.contra-vouchers.edit', compact('voucher', 'cashBankLedgers', 'settings'));
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::where('type', 'Contra')->where('status', 'Draft')->findOrFail($id);
        
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'transfer_mode' => 'required|in:Bank Transfer,Cash Deposit,Cash Withdrawal,Internal Transfer,Cheque',
            'source_ledger_id' => 'required|exists:ledgers,id|different:destination_ledger_id',
            'destination_ledger_id' => 'required|exists:ledgers,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'reference_date' => 'nullable|date',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules, [
            'source_ledger_id.different' => 'Source and Destination ledgers cannot be the same.'
        ]);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $sourceLedger = Ledger::findOrFail($request->source_ledger_id);
            $destinationLedger = Ledger::findOrFail($request->destination_ledger_id);
            
            $this->validateCashBankClassification($sourceLedger, $destinationLedger);

            $metadata = [
                'transfer_mode' => $request->transfer_mode,
                'source_ledger_id' => $request->source_ledger_id,
                'destination_ledger_id' => $request->destination_ledger_id,
                'amount' => $request->amount,
                'reference_number' => $request->reference_number,
                'reference_date' => $request->reference_date,
            ];

            if ($request->action === 'draft') {
                $voucher->update([
                    'date' => $request->date,
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'draft_data' => [
                        'metadata' => $metadata
                    ]
                ]);
                DB::commit();
                return redirect()->route('contra-vouchers.index')->with('success', 'Contra Voucher draft updated.');
            } else {
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Contra',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null,
                    'metadata' => $metadata
                ];
                
                $entries = $this->formatEntries($sourceLedger->id, $destinationLedger->id, $request->amount);
                $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                $voucher->delete();
                
                DB::commit();
                return redirect()->route('contra-vouchers.index')->with('success', 'Contra Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            }

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function post($id)
    {
        $voucher = Voucher::where('type', 'Contra')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Explicit idempotency check
            if ($voucher->status === 'Posted') {
                throw new Exception("Contra Voucher is already posted.");
            }

            if ($voucher->status !== 'Draft' || !$voucher->draft_data) {
                throw new Exception("Invalid voucher state for posting.");
            }

            $finYear = $this->fyService->validatePostingDate($voucher->date);
            $metadata = $voucher->draft_data['metadata'];
            $sourceLedger = Ledger::findOrFail($metadata['source_ledger_id']);
            $destinationLedger = Ledger::findOrFail($metadata['destination_ledger_id']);
            
            $this->validateCashBankClassification($sourceLedger, $destinationLedger);

            $voucherData = [
                'date' => $voucher->date,
                'type' => 'Contra',
                'narration' => $voucher->narration,
                'financial_year_id' => $finYear->id,
                'created_by' => $voucher->created_by,
                'status' => 'Posted',
                'draft_data' => null,
                'metadata' => $metadata
            ];
            
            $entries = $this->formatEntries($sourceLedger->id, $destinationLedger->id, $metadata['amount']);
            
            $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
            $voucher->delete();
            
            DB::commit();
            return redirect()->route('contra-vouchers.index')->with('success', 'Contra Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $voucher = Voucher::where('type', 'Contra')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('contra-vouchers.index')->with('success', 'Draft Contra Voucher deleted.');
            } else {
                throw new Exception("Cancellation of posted vouchers requires reversal logic, which is not fully implemented here yet.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function validateCashBankClassification(Ledger $source, Ledger $destination)
    {
        if ($source->id === $destination->id) {
            throw new Exception("Source and Destination ledgers cannot be the same.");
        }

        if (!$this->isCashOrBank($source)) {
            throw new Exception("Contra Voucher can only transfer funds between Cash/Bank ledgers. Invalid Source Ledger: {$source->name}");
        }

        if (!$this->isCashOrBank($destination)) {
            throw new Exception("Contra Voucher can only transfer funds between Cash/Bank ledgers. Invalid Destination Ledger: {$destination->name}");
        }
        
        $settings = $this->settingsService->getSettings();
        if (!$source->is_active && $settings->prevent_inactive_ledger_posting) {
            throw new Exception("Cannot post to inactive source ledger: {$source->name}");
        }
        if (!$destination->is_active && $settings->prevent_inactive_ledger_posting) {
            throw new Exception("Cannot post to inactive destination ledger: {$destination->name}");
        }
    }

    private function formatEntries($sourceLedgerId, $destinationLedgerId, $amount)
    {
        return [
            [
                'ledger_id' => $destinationLedgerId,
                'type' => 'Dr',
                'amount' => $amount,
                'narration' => null
            ],
            [
                'ledger_id' => $sourceLedgerId,
                'type' => 'Cr',
                'amount' => $amount,
                'narration' => null
            ]
        ];
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
