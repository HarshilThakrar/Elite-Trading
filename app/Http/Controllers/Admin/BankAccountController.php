<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::all();
        $activeTab = 'accounts'; // Default tab
        return view('admin.bank-accounts.index', compact('accounts', 'activeTab'));
    }

    public function feeds()
    {
        $activeTab = 'feeds';
        $accounts = BankAccount::all();
        return view('admin.bank-accounts.feeds', compact('activeTab', 'accounts'));
    }

    public function history(BankAccount $bankAccount)
    {
        // Get the associated ledger
        $ledger = $bankAccount->ledger;
        
        // Fetch journal entries for this ledger
        $entries = collect();
        if ($ledger) {
            $entries = \App\Models\JournalEntry::where('ledger_id', $ledger->id)
                ->with('voucher')
                ->orderBy('created_at', 'asc')
                ->get();
        }
        
        return view('admin.bank-accounts.history', compact('bankAccount', 'ledger', 'entries'));
    }

    public function create()
    {
        return view('admin.bank-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required|unique:bank_accounts',
            'ifsc_code' => 'nullable',
            'branch' => 'nullable',
            'opening_balance' => 'numeric'
        ]);

        $account = BankAccount::create($data);

        // Auto-create Ledger
        $group = \App\Models\AccountGroup::where('name', 'Bank Accounts')->first();
        if ($group) {
            \App\Models\Ledger::create([
                'name' => $account->bank_name . ' - ' . substr($account->account_number, -4),
                'account_group_id' => $group->id,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_type' => 'Dr',
                'is_system' => false,
                'type' => 'bank_account',
                'reference_id' => $account->id,
            ]);
        }

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account added.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required|unique:bank_accounts,account_number,' . $bankAccount->id,
            'ifsc_code' => 'nullable',
            'branch' => 'nullable'
        ]);

        $bankAccount->update($data);
        
        $ledger = $bankAccount->ledger;
        if ($ledger) {
            $ledger->update(['name' => $bankAccount->bank_name . ' - ' . substr($bankAccount->account_number, -4)]);
        }

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account updated.');
    }

    public function fetchIfsc($ifsc)
    {
        $ifsc = trim(strtoupper($ifsc));
        
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid IFSC code format.'
            ]);
        }

        try {
            $response = Http::timeout(10)->get("https://ifsclookup.in/api/ifsc/{$ifsc}");

            if ($response->successful()) {
                // Assuming the API returns JSON structure: { "bank_name": "...", "branch": "...", "city": "...", "state": "...", "micr": "..." }
                // Need to parse and return correctly. Let's return the raw JSON body if it's an object.
                $data = $response->json();
                
                if (isset($data['bank_name']) || isset($data['BANK'])) {
                    // standardize response
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'bank_name' => $data['BANK'] ?? $data['bank_name'] ?? null,
                            'branch'    => $data['BRANCH'] ?? $data['branch'] ?? null,
                            'city'      => $data['CITY'] ?? $data['city'] ?? null,
                            'state'     => $data['STATE'] ?? $data['state'] ?? null,
                            'micr'      => $data['MICR'] ?? $data['micr'] ?? null,
                            'address'   => $data['ADDRESS'] ?? $data['address'] ?? null,
                        ]
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or unavailable IFSC code.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not fetch details. Please enter manually.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection timeout or error. Please enter manually.'
            ]);
        }
    }

    public function toggleStatus(BankAccount $bankAccount)
    {
        $bankAccount->update(['is_active' => !$bankAccount->is_active]);
        
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'is_active' => $bankAccount->is_active
        ]);
    }
}