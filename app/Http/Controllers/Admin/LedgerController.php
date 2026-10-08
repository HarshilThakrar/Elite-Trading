<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $query = Ledger::with('accountGroup')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ledger_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account_group_id')) {
            $query->where('account_group_id', $request->account_group_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active');
        }

        $ledgers = $query->paginate(20)->withQueryString();
        
        $groups = AccountGroup::orderBy('name')->get();

        return view('admin.ledgers.index', compact('ledgers', 'groups'));
    }

    public function create()
    {
        $groups = AccountGroup::orderBy('name')->get();
        $customers = Customer::where('status', 1)->orderBy('company_name')->get();
        $vendors = Vendor::where('status', 1)->orderBy('company_name')->get();

        return view('admin.ledgers.create', compact('groups', 'customers', 'vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ledgers,name',
            'ledger_code' => 'nullable|string|max:255|unique:ledgers,ledger_code',
            'account_group_id' => 'required|exists:account_groups,id',
            'type' => 'nullable|string',
            'reference_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'customer' || $request->type === 'vendor') {
                        $exists = Ledger::where('type', $request->type)
                            ->where('reference_id', $value)
                            ->exists();
                        if ($exists) {
                            $fail("This {$request->type} is already linked to an existing accounting ledger.");
                        }
                    }
                },
            ],
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_type' => 'nullable|in:Dr,Cr',
            'is_active' => 'boolean',
        ]);

        $metadata = null;
        if ($request->type === 'bank') {
            $metadata = [
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'ifsc' => $request->ifsc,
                'branch' => $request->branch,
                'account_type' => $request->account_type,
            ];
        }

        DB::transaction(function () use ($request, $metadata) {
            Ledger::create([
                'name' => $request->name,
                'ledger_code' => $request->ledger_code,
                'account_group_id' => $request->account_group_id,
                'type' => $request->type,
                'reference_id' => in_array($request->type, ['customer', 'vendor']) ? $request->reference_id : null,
                'opening_balance' => $request->opening_balance ?? 0,
                'opening_balance_type' => $request->opening_balance_type,
                'opening_balance_date' => $request->opening_balance_date,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'metadata' => $metadata,
            ]);
        });

        return redirect()->route('ledgers.index')->with('success', 'Ledger created successfully.');
    }

    public function show(Ledger $ledger)
    {
        $ledger->load('accountGroup');
        
        // Compute running balance based on JournalEntries
        $entries = $ledger->entries()->with('voucher')->orderBy('bank_date', 'desc')->orderBy('id', 'desc')->paginate(20);

        // Current Balance Logic
        $totalDr = $ledger->entries()->where('type', 'Dr')->sum('amount');
        $totalCr = $ledger->entries()->where('type', 'Cr')->sum('amount');
        
        $openingAmount = $ledger->opening_balance ?? 0;
        if ($ledger->opening_balance_type === 'Dr') {
            $totalDr += $openingAmount;
        } else if ($ledger->opening_balance_type === 'Cr') {
            $totalCr += $openingAmount;
        }

        $currentBalanceAmount = abs($totalDr - $totalCr);
        $currentBalanceType = $totalDr >= $totalCr ? 'Dr' : 'Cr';
        if ($currentBalanceAmount == 0) {
            $currentBalanceType = '';
        }

        return view('admin.ledgers.show', compact('ledger', 'entries', 'currentBalanceAmount', 'currentBalanceType'));
    }

    public function edit(Ledger $ledger)
    {
        $groups = AccountGroup::orderBy('name')->get();
        $hasTransactions = $ledger->entries()->exists();
        
        $customers = Customer::where('status', 1)->orderBy('company_name')->get();
        $vendors = Vendor::where('status', 1)->orderBy('company_name')->get();

        return view('admin.ledgers.edit', compact('ledger', 'groups', 'hasTransactions', 'customers', 'vendors'));
    }

    public function update(Request $request, Ledger $ledger)
    {
        $hasTransactions = $ledger->entries()->exists();

        $rules = [
            'name' => 'required|string|max:255|unique:ledgers,name,' . $ledger->id,
            'ledger_code' => 'nullable|string|max:255|unique:ledgers,ledger_code,' . $ledger->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // If no transactions, allow modifying account group and type and reference
        if (!$hasTransactions && !$ledger->is_system) {
            $rules['account_group_id'] = 'required|exists:account_groups,id';
            $rules['opening_balance'] = 'nullable|numeric|min:0';
            $rules['opening_balance_type'] = 'nullable|in:Dr,Cr';
            $rules['type'] = 'nullable|string';
            $rules['reference_id'] = [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request, $ledger) {
                    if ($request->type === 'customer' || $request->type === 'vendor') {
                        $exists = Ledger::where('type', $request->type)
                            ->where('reference_id', $value)
                            ->where('id', '!=', $ledger->id)
                            ->exists();
                        if ($exists) {
                            $fail("This {$request->type} is already linked to another accounting ledger.");
                        }
                    }
                },
            ];
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $ledger, $hasTransactions) {
            $ledger->name = $request->name;
            $ledger->ledger_code = $request->ledger_code;
            $ledger->description = $request->description;
            $ledger->is_active = $request->boolean('is_active', true);
            
            if ($request->type === 'bank' || $ledger->type === 'bank') {
                $metadata = $ledger->metadata ?? [];
                if ($request->has('bank_name')) $metadata['bank_name'] = $request->bank_name;
                if ($request->has('account_number')) $metadata['account_number'] = $request->account_number;
                if ($request->has('ifsc')) $metadata['ifsc'] = $request->ifsc;
                if ($request->has('branch')) $metadata['branch'] = $request->branch;
                if ($request->has('account_type')) $metadata['account_type'] = $request->account_type;
                $ledger->metadata = $metadata;
            }

            if (!$hasTransactions && !$ledger->is_system) {
                $ledger->account_group_id = $request->account_group_id;
                $ledger->opening_balance = $request->opening_balance ?? 0;
                $ledger->opening_balance_type = $request->opening_balance_type;
                $ledger->opening_balance_date = $request->opening_balance_date;
                $ledger->type = $request->type;
                $ledger->reference_id = in_array($request->type, ['customer', 'vendor']) ? $request->reference_id : null;
            }

            $ledger->save();
        });

        return redirect()->route('ledgers.index')->with('success', 'Ledger updated successfully.');
    }

    public function destroy(Ledger $ledger)
    {
        if ($ledger->is_system) {
            return redirect()->route('ledgers.index')->with('error', 'Cannot delete a system ledger.');
        }

        if ($ledger->entries()->exists()) {
            return redirect()->route('ledgers.index')->with('error', 'Ledger cannot be deleted because accounting transactions exist. You may deactivate it instead.');
        }

        $ledger->delete();

        return redirect()->route('ledgers.index')->with('success', 'Ledger deleted successfully.');
    }
}
