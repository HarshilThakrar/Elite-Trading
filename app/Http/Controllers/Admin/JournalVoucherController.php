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

class JournalVoucherController extends Controller
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
        $query = Voucher::where('type', 'Journal')->with(['financialYear']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%");
            });
        }
        
        $vouchers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('admin.journal-vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $settings = $this->settingsService->getSettings();
        
        // Active ledgers query
        $ledgersQuery = Ledger::where('is_active', true)->orderBy('name');
        
        $ledgers = $ledgersQuery->get();
        return view('admin.journal-vouchers.create', compact('ledgers', 'settings'));
    }

    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            // Validate Date & Financial Year Rules
            $finYear = $this->fyService->validatePostingDate($request->date);
            
            $lines = $this->parseAndValidateLines($request->lines);

            $draftData = [
                'lines' => $lines
            ];

            if ($request->action === 'draft') {
                $voucher = Voucher::create([
                    'voucher_number' => 'DRAFT-' . time() . '-' . rand(100, 999), // Temp
                    'date' => $request->date,
                    'type' => 'Journal',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Draft',
                    'draft_data' => $draftData
                ]);
                
                DB::commit();
                return redirect()->route('journal-vouchers.index')->with('success', 'Journal Voucher saved as Draft.');
            } else {
                // Post directly
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Journal',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null // Clear draft data on post
                ];
                
                $entries = $this->formatLinesForAccounting($lines);
                
                $voucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                DB::commit();
                return redirect()->route('journal-vouchers.index')->with('success', 'Journal Voucher posted successfully (No: ' . $voucher->voucher_number . ').');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = Voucher::where('type', 'Journal')->with(['entries.ledger', 'financialYear'])->findOrFail($id);
        return view('admin.journal-vouchers.show', compact('voucher'));
    }

    public function edit($id)
    {
        $voucher = Voucher::where('type', 'Journal')->where('status', 'Draft')->findOrFail($id);
        $settings = $this->settingsService->getSettings();
        $ledgers = Ledger::where('is_active', true)->orderBy('name')->get();
        return view('admin.journal-vouchers.edit', compact('voucher', 'ledgers', 'settings'));
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::where('type', 'Journal')->where('status', 'Draft')->findOrFail($id);
        
        $rules = [
            'date' => 'required|date',
            'narration' => $this->settingsService->isNarrationRequired() ? 'required|string|max:255' : 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.narration' => 'nullable|string|max:255',
            'action' => 'required|in:draft,post'
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $finYear = $this->fyService->validatePostingDate($request->date);
            $lines = $this->parseAndValidateLines($request->lines);

            if ($request->action === 'draft') {
                $voucher->update([
                    'date' => $request->date,
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'draft_data' => ['lines' => $lines]
                ]);
                DB::commit();
                return redirect()->route('journal-vouchers.index')->with('success', 'Journal Voucher draft updated.');
            } else {
                // To post an existing draft via AccountingEngine, we delete the draft and let AccountingEngine create a fresh posted voucher.
                // Or pass the voucher ID to AccountingEngine if it supports it. AccountingEngine->postVoucher currently creates a NEW voucher.
                // We will create the new one and delete the draft to ensure atomicity.
                $voucherData = [
                    'date' => $request->date,
                    'type' => 'Journal',
                    'narration' => $request->narration,
                    'financial_year_id' => $finYear->id,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'Posted',
                    'draft_data' => null // keep for audit (Wait, actually I should set to null for posted)
                ];
                
                $entries = $this->formatLinesForAccounting($lines);
                
                $postedVoucher = $this->accountingEngine->postVoucher($voucherData, $entries);
                
                $voucher->delete(); // Remove old draft
                
                DB::commit();
                return redirect()->route('journal-vouchers.index')->with('success', 'Journal Voucher posted successfully (No: ' . $postedVoucher->voucher_number . ').');
            }

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function cancel($id)
    {
        // For drafts, we can just delete. For posted, AccountingEngine reversal is needed if supported.
        $voucher = Voucher::where('type', 'Journal')->findOrFail($id);
        
        try {
            DB::beginTransaction();
            if ($voucher->status === 'Draft') {
                $voucher->delete();
                DB::commit();
                return redirect()->route('journal-vouchers.index')->with('success', 'Draft Journal Voucher deleted.');
            } else {
                throw new Exception("Cancellation of posted vouchers is not yet implemented.");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function parseAndValidateLines($rawLines)
    {
        $validLines = [];
        $totalDr = 0;
        $totalCr = 0;
        
        $settings = $this->settingsService->getSettings();

        foreach ($rawLines as $line) {
            $debit = (float)($line['debit'] ?? 0);
            $credit = (float)($line['credit'] ?? 0);
            
            if ($debit == 0 && $credit == 0) {
                continue; // Skip zero lines
            }
            if ($debit > 0 && $credit > 0) {
                throw new Exception("A single ledger line cannot have both Debit and Credit amounts.");
            }
            
            // Check inactive ledger setting
            $ledger = Ledger::find($line['ledger_id']);
            if (!$ledger) {
                throw new Exception("Invalid Ledger selected.");
            }
            if (!$ledger->is_active && $settings->prevent_inactive_ledger_posting) {
                throw new Exception("Cannot post to inactive ledger: {$ledger->name}");
            }
            
            $totalDr += $debit;
            $totalCr += $credit;
            
            $validLines[] = [
                'ledger_id' => $line['ledger_id'],
                'debit' => $debit,
                'credit' => $credit,
                'narration' => $line['narration'] ?? null,
                'type' => $debit > 0 ? 'Dr' : 'Cr',
                'amount' => $debit > 0 ? $debit : $credit,
                'ledger_name' => $ledger->name // Helpful for UI when returning validation errors
            ];
        }

        if (count($validLines) < 2) {
            throw new Exception("Journal Voucher requires at least two valid accounting lines.");
        }

        if (round($totalDr, 2) != round($totalCr, 2)) {
            throw new Exception("Journal Voucher is not balanced. Total Debit (" . round($totalDr, 2) . ") must equal Total Credit (" . round($totalCr, 2) . ").");
        }
        
        if (round($totalDr, 2) <= 0) {
            throw new Exception("Total Debit/Credit must be greater than zero.");
        }

        return $validLines;
    }

    private function formatLinesForAccounting($lines)
    {
        return array_map(function($line) {
            return [
                'ledger_id' => $line['ledger_id'],
                'type' => $line['type'],
                'amount' => $line['amount'],
                'narration' => $line['narration']
            ];
        }, $lines);
    }
}
