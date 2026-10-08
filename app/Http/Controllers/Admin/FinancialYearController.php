<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Throwable;

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
            'name' => 'required|string|max:255|unique:financial_years,name',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');

            $this->service->validateOverlap($startDate, $endDate);
            
            $fy = FinancialYear::create([
                'name' => trim($request->name),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            
            // Log creation safely
            if (function_exists('activity')) {
                activity()->log("Created Financial Year: {$fy->name}");
            }

            return redirect()->route('financial-years.index')->with('success', 'Financial Year created successfully.');
        } catch (Throwable $e) {
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_years', 'name')->ignore($financialYear->id)
            ],
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');

            $this->service->validateOverlap($startDate, $endDate, $financialYear->id);
            
            $financialYear->update([
                'name' => trim($request->name),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            
            // Log update safely
            if (function_exists('activity')) {
                activity()->log("Updated Financial Year: {$financialYear->name}");
            }

            return redirect()->route('financial-years.index')->with('success', 'Financial Year updated successfully.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(FinancialYear $financialYear)
    {
        try {
            // Block deletion if there are entries or vouchers
            $vouchersCount = \App\Models\Voucher::where('financial_year_id', $financialYear->id)->count();
            if ($vouchersCount > 0) {
                return back()->with('error', 'Cannot delete financial year because it contains accounting vouchers.');
            }

            $name = $financialYear->name;
            $financialYear->delete();
            
            if (function_exists('activity')) {
                activity()->log("Deleted Financial Year: {$name}");
            }
            
            return redirect()->route('financial-years.index')->with('success', 'Financial Year deleted successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function activate(FinancialYear $financialYear)
    {
        try {
            $this->service->activateFinancialYear($financialYear);
            if (function_exists('activity')) {
                activity()->log("Activated Financial Year: {$financialYear->name}");
            }
            return back()->with('success', 'Financial Year activated successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(FinancialYear $financialYear)
    {
        try {
            $this->service->closeFinancialYear($financialYear);
            if (function_exists('activity')) {
                activity()->log("Closed Financial Year: {$financialYear->name}");
            }
            return back()->with('success', 'Financial Year closed successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopen(FinancialYear $financialYear)
    {
        try {
            $this->service->reopenFinancialYear($financialYear);
            if (function_exists('activity')) {
                activity()->log("Reopened Financial Year: {$financialYear->name}");
            }
            return back()->with('success', 'Financial Year reopened successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

