<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Models\Ledger;
use Illuminate\Http\Request;

class ChartOfAccountsController extends Controller
{
    /**
     * Display the Chart of Accounts or handle search/ajax.
     */
    public function index(Request $request)
    {
        // AJAX: Load children for a specific node
        if ($request->ajax() && $request->has('parent_id')) {
            $parentId = $request->parent_id;
            
            $groups = AccountGroup::where('parent_id', $parentId)
                ->withCount(['children', 'ledgers'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
                
            $ledgers = Ledger::where('account_group_id', $parentId)
                ->orderBy('name')
                ->get();
                
            return response()->json([
                'groups' => $groups,
                'ledgers' => $ledgers
            ]);
        }
        
        // Search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            
            $groups = AccountGroup::where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->get();
                
            $ledgers = Ledger::with('accountGroup')->where('name', 'like', "%{$searchTerm}%")
                ->get();
                
            // Resolve paths efficiently (this uses the static cache in the models)
            $groups->each(function($group) {
                $group->full_path = $group->getPath();
                $group->is_ledger = false;
            });
            
            $ledgers->each(function($ledger) {
                $ledger->full_path = $ledger->getPath();
                $ledger->is_ledger = true;
                $ledger->nature = $ledger->accountGroup ? $ledger->accountGroup->nature : '-';
                $ledger->normal_balance = $ledger->accountGroup ? $ledger->accountGroup->normal_balance : '-';
            });
            
            $results = $groups->merge($ledgers);
            
            return view('admin.chart-of-accounts.index', [
                'isSearch' => true,
                'results' => $results,
                'searchTerm' => $searchTerm
            ]);
        }
        
        // Initial Root Load
        $roots = AccountGroup::whereNull('parent_id')
            ->withCount(['children', 'ledgers'])
            ->orderBy('sort_order')
            ->orderBy('nature')
            ->get();
            
        // For standard root ordering: Assets, Liabilities, Capital, Income, Expenses
        $order = ['Assets' => 1, 'Liabilities' => 2, 'Capital' => 3, 'Income' => 4, 'Expenses' => 5];
        $roots = $roots->sortBy(function($root) use ($order) {
            return $order[$root->nature] ?? 99;
        })->values();

        return view('admin.chart-of-accounts.index', [
            'isSearch' => false,
            'roots' => $roots
        ]);
    }
}
