<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use Exception;

class FinancialYearController extends Controller
{
    protected $service;

    public function __construct(FinancialYearService $service)
    {
        $this->service = $service;
        // Optionally apply middlewares for permissions
        // $this->middleware('permission:manage financial years');
    }

    public function index()
    {
        $financial_years = FinancialYear::orderBy('start_date', 'desc')->get();
        return view('admin.financial-years.index', compact('financial_years'));
    }

    public function create()
    {
        return view('admin.financial-years.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $this->service->validateOverlap($request->start_date, $request->end_date);
            FinancialYear::create($request->only(['name', 'start_date', 'end_date']));
            
            // Log creation
            activity()->log("Created Financial Year: {$request->name}");

            return redirect()->route('financial-years.index')->with('success', 'Financial Year created.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(FinancialYear $financialYear)
    {
        return view('admin.financial-years.show', compact('financialYear'));
    }

    public function edit(FinancialYear $financialYear)
    {
        return view('admin.financial-years.edit', compact('financialYear'));
    }

    public function update(Request $request, FinancialYear $financialYear)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $this->service->validateOverlap($request->start_date, $request->end_date, $financialYear->id);
            $financialYear->update($request->only(['name', 'start_date', 'end_date']));
            
            activity()->log("Updated Financial Year: {$financialYear->name}");

            return redirect()->route('financial-years.index')->with('success', 'Financial Year updated.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(FinancialYear $financialYear)
    {
        // Block deletion if there are entries or vouchers
        $vouchersCount = \App\Models\Voucher::where('financial_year_id', $financialYear->id)->count();
        if ($vouchersCount > 0) {
            return back()->with('error', 'Cannot delete financial year because it contains accounting vouchers.');
        }

        $financialYear->delete();
        activity()->log("Deleted Financial Year: {$financialYear->name}");
        return redirect()->route('financial-years.index')->with('success', 'Financial Year deleted.');
    }

    public function activate(FinancialYear $financialYear)
    {
        try {
            $this->service->activateFinancialYear($financialYear);
            activity()->log("Activated Financial Year: {$financialYear->name}");
            return back()->with('success', 'Financial Year activated successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(FinancialYear $financialYear)
    {
        try {
            $this->service->closeFinancialYear($financialYear);
            activity()->log("Closed Financial Year: {$financialYear->name}");
            return back()->with('success', 'Financial Year closed successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopen(FinancialYear $financialYear)
    {
        try {
            $this->service->reopenFinancialYear($financialYear);
            activity()->log("Reopened Financial Year: {$financialYear->name}");
            return back()->with('success', 'Financial Year reopened successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
