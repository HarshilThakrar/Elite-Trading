<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Models\BankStatementTransaction;
use App\Services\BankReconciliationService;
use App\Services\BankStatementImportService;

class BankReconciliationController extends Controller
{
    protected $reconService;
    protected $importService;

    public function __construct(BankReconciliationService $reconService, BankStatementImportService $importService)
    {
        $this->reconService = $reconService;
        $this->importService = $importService;
    }

    private function getBankLedgers()
    {
        // Recursively find all ledgers under "Bank Accounts"
        $bankGroup = AccountGroup::where('name', 'Bank Accounts')->first();
        if (!$bankGroup) return collect();

        // Very basic hierarchy resolution for this example (assuming flat or 1 level deep)
        // A better approach would be to recursively fetch child groups.
        $groupIds = AccountGroup::where('parent_id', $bankGroup->id)->pluck('id')->toArray();
        $groupIds[] = $bankGroup->id;

        return Ledger::whereIn('account_group_id', $groupIds)->where('is_active', true)->orderBy('name')->get();
    }

    public function index(Request $request)
    {
        $bankLedgers = $this->getBankLedgers();
        
        $ledgerId = $request->get('bank_ledger_id');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        $summary = null;
        if ($ledgerId) {
            $summary = $this->reconService->getSummary($ledgerId, $fromDate, $toDate);
        }

        return view('admin.bank-reconciliation.index', compact('bankLedgers', 'ledgerId', 'fromDate', 'toDate', 'summary'));
    }

    public function reconcile(Request $request, $ledgerId)
    {
        $ledger = Ledger::findOrFail($ledgerId);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        
        $bookTxns = $this->reconService->getBookTransactions($ledgerId, $fromDate, $toDate);
        $bankTxns = $this->reconService->getBankTransactions($ledgerId, $fromDate, $toDate);
        $summary = $this->reconService->getSummary($ledgerId, $fromDate, $toDate);

        return view('admin.bank-reconciliation.reconcile', compact('ledger', 'fromDate', 'toDate', 'bookTxns', 'bankTxns', 'summary'));
    }

    public function autoMatch(Request $request, $ledgerId)
    {
        $tolerance = $request->get('tolerance', 3);
        $matches = $this->reconService->autoMatch($ledgerId, $tolerance);

        return back()->with('success', "Auto-match complete. {$matches} transactions reconciled.");
    }

    public function match(Request $request, $ledgerId)
    {
        $request->validate([
            'journal_entry_id' => 'required|exists:journal_entries,id',
            'bank_statement_transaction_id' => 'required|exists:bank_statement_transactions,id',
        ]);

        try {
            $this->reconService->matchTransaction($request->journal_entry_id, $request->bank_statement_transaction_id);
            return response()->json(['success' => true, 'message' => 'Transactions matched successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function unmatch(Request $request, $ledgerId, $statementId)
    {
        try {
            $this->reconService->unmatchTransaction($statementId);
            return back()->with('success', 'Transaction unmatched successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function ignore(Request $request, $ledgerId, $statementId)
    {
        try {
            $stmt = BankStatementTransaction::where('bank_ledger_id', $ledgerId)->findOrFail($statementId);
            $stmt->reconciliation_status = 'Ignored';
            $stmt->save();
            return back()->with('success', 'Transaction excluded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function import(Request $request, $ledgerId)
    {
        $ledger = Ledger::findOrFail($ledgerId);
        
        if ($request->isMethod('post')) {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt'
            ]);
            
            // Move file to temporary location for mapping
            $path = $request->file('csv_file')->store('temp');
            
            // Extract headers
            $headers = [];
            if (($handle = fopen(storage_path('app/' . $path), "r")) !== FALSE) {
                $headers = fgetcsv($handle);
                fclose($handle);
            }
            
            return view('admin.bank-reconciliation.import-map', compact('ledger', 'path', 'headers'));
        }

        return view('admin.bank-reconciliation.import', compact('ledger'));
    }

    public function processImport(Request $request, $ledgerId)
    {
        $request->validate([
            'file_path' => 'required',
            'mapping.transaction_date' => 'required',
        ]);

        $filePath = storage_path('app/' . $request->file_path);
        
        if (!file_exists($filePath)) {
            return redirect()->route('bank-reconciliation.index')->with('error', 'Uploaded file expired or not found.');
        }

        try {
            $results = $this->importService->importMappedData($ledgerId, $filePath, $request->mapping);
            unlink($filePath); // Clean up
            
            $msg = "Import Complete. Imported: {$results['imported']}, Duplicates skipped: {$results['duplicates']}, Errors: {$results['errors']}.";
            if ($results['errors'] > 0) {
                return redirect()->route('bank-reconciliation.reconcile', ['ledgerId' => $ledgerId])
                    ->with('warning', $msg . ' Check logs for error details.');
            }
            return redirect()->route('bank-reconciliation.reconcile', ['ledgerId' => $ledgerId])
                ->with('success', $msg);
                
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
