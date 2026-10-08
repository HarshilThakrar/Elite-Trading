<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OpeningBalance;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\AccountGroup;
use App\Services\FinancialYearService;
use Illuminate\Support\Facades\DB;
use Exception;

class OpeningBalanceController extends Controller
{
    protected $fyService;

    public function __construct(FinancialYearService $fyService)
    {
        $this->fyService = $fyService;
    }

    public function index(Request $request)
    {
        // By default, select current FY
        $currentFy = $this->fyService->getCurrentFinancialYear();
        $selectedFyId = $request->get('financial_year_id', $currentFy ? $currentFy->id : null);
        
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $selectedFy = FinancialYear::find($selectedFyId);

        $query = OpeningBalance::with(['ledger.accountGroup', 'financialYear', 'createdBy'])
            ->when($selectedFyId, function($q) use ($selectedFyId) {
                return $q->where('financial_year_id', $selectedFyId);
            });

        // Search
        if ($search = $request->get('search')) {
            $query->whereHas('ledger', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ledger_code', 'like', "%{$search}%");
            });
        }

        $openingBalances = $query->paginate(20)->appends($request->all());

        // Calculate Totals for the selected FY
        $totalDr = 0;
        $totalCr = 0;
        $difference = 0;
        $isBalanced = true;

        if ($selectedFyId) {
            $totalDr = OpeningBalance::where('financial_year_id', $selectedFyId)->where('type', 'Dr')->sum('amount');
            $totalCr = OpeningBalance::where('financial_year_id', $selectedFyId)->where('type', 'Cr')->sum('amount');
            $difference = abs($totalDr - $totalCr);
            $isBalanced = ($totalDr == $totalCr);
        }

        return view('admin.opening-balances.index', compact('openingBalances', 'financialYears', 'selectedFyId', 'selectedFy', 'totalDr', 'totalCr', 'difference', 'isBalanced'));
    }

    public function create(Request $request)
    {
        $currentFy = $this->fyService->getCurrentFinancialYear();
        $selectedFyId = $request->get('financial_year_id', $currentFy ? $currentFy->id : null);
        
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        // Load all active ledgers
        $ledgers = Ledger::where('is_active', true)->orderBy('name')->get();

        return view('admin.opening-balances.create', compact('financialYears', 'ledgers', 'selectedFyId', 'currentFy'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'financial_year_id' => 'required|exists:financial_years,id',
            'ledger_id' => 'required|exists:ledgers,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:Dr,Cr',
            'opening_date' => 'required|date',
            'narration' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $fy = FinancialYear::findOrFail($request->financial_year_id);
            if ($this->fyService->isClosed($fy)) {
                throw new Exception("Cannot create opening balance for a closed financial year.");
            }

            // Ensure date is within FY
            $date = \Carbon\Carbon::parse($request->opening_date)->format('Y-m-d');
            $fyStartDate = \Carbon\Carbon::parse($fy->start_date)->format('Y-m-d');
            $fyEndDate = \Carbon\Carbon::parse($fy->end_date)->format('Y-m-d');

            if ($date < $fyStartDate || $date > $fyEndDate) {
                throw new Exception("Opening date must be within the selected Financial Year ({$fy->start_date} to {$fy->end_date}).");
            }

            // Uniqueness check
            $exists = OpeningBalance::where('financial_year_id', $fy->id)->where('ledger_id', $request->ledger_id)->exists();
            if ($exists) {
                throw new Exception("Opening balance already exists for this ledger in the selected financial year.");
            }

            // Save
            DB::transaction(function() use ($request) {
                OpeningBalance::create([
                    'financial_year_id' => $request->financial_year_id,
                    'ledger_id' => $request->ledger_id,
                    'amount' => $request->amount,
                    'type' => $request->type,
                    'opening_date' => $request->opening_date,
                    'narration' => $request->narration,
                    'reference' => $request->reference,
                    'created_by' => auth()->id()
                ]);
            });

            return redirect()->route('opening-balances.index', ['financial_year_id' => $request->financial_year_id])
                ->with('success', 'Opening balance created successfully.');
                
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(OpeningBalance $openingBalance)
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        $ledgers = Ledger::where('is_active', true)->orderBy('name')->get();
        return view('admin.opening-balances.edit', compact('openingBalance', 'financialYears', 'ledgers'));
    }

    public function update(Request $request, OpeningBalance $openingBalance)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:Dr,Cr',
            'opening_date' => 'required|date',
            'narration' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            if ($this->fyService->isClosed($openingBalance->financialYear)) {
                throw new Exception("Cannot edit opening balance for a closed financial year.");
            }

            $fy = $openingBalance->financialYear;
            $date = \Carbon\Carbon::parse($request->opening_date)->format('Y-m-d');
            $fyStartDate = \Carbon\Carbon::parse($fy->start_date)->format('Y-m-d');
            $fyEndDate = \Carbon\Carbon::parse($fy->end_date)->format('Y-m-d');

            if ($date < $fyStartDate || $date > $fyEndDate) {
                throw new Exception("Opening date must be within the selected Financial Year ({$fy->start_date} to {$fy->end_date}).");
            }

            DB::transaction(function() use ($request, $openingBalance) {
                $openingBalance->update([
                    'amount' => $request->amount,
                    'type' => $request->type,
                    'opening_date' => $request->opening_date,
                    'narration' => $request->narration,
                    'reference' => $request->reference,
                    'updated_by' => auth()->id()
                ]);
            });

            return redirect()->route('opening-balances.index', ['financial_year_id' => $openingBalance->financial_year_id])
                ->with('success', 'Opening balance updated successfully.');
                
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(OpeningBalance $openingBalance)
    {
        try {
            if ($this->fyService->isClosed($openingBalance->financialYear)) {
                throw new Exception("Cannot delete opening balance for a closed financial year.");
            }

            $fyId = $openingBalance->financial_year_id;
            
            DB::transaction(function() use ($openingBalance) {
                $openingBalance->delete();
            });

            return redirect()->route('opening-balances.index', ['financial_year_id' => $fyId])
                ->with('success', 'Opening balance deleted successfully.');
                
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
