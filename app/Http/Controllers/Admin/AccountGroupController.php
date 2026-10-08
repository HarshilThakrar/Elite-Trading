<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AccountGroup::with('parent')->withCount(['ledgers', 'children']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('nature')) {
            $query->where('nature', $request->nature);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $groups = $query->orderBy('nature')->orderBy('sort_order')->orderBy('name')->paginate(20);
        
        $rootNatures = ['Assets', 'Liabilities', 'Income', 'Expenses', 'Capital'];

        return view('admin.account-groups.index', compact('groups', 'rootNatures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentGroups = AccountGroup::where('is_active', true)->orderBy('name')->get();
        $rootNatures = ['Assets', 'Liabilities', 'Income', 'Expenses', 'Capital'];
        
        return view('admin.account-groups.create', compact('parentGroups', 'rootNatures'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:account_groups,name',
            'parent_id' => 'nullable|exists:account_groups,id',
            'nature' => 'required|in:Assets,Liabilities,Income,Expenses,Capital',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $data = $request->all();
        
        // Ensure child inherits nature from parent if parent is provided
        if ($request->parent_id) {
            $parent = AccountGroup::find($request->parent_id);
            if ($parent->nature !== $request->nature) {
                return back()->withInput()->withErrors(['nature' => 'The accounting nature must match the parent group\'s nature (' . $parent->nature . ').']);
            }
        }

        $data['normal_balance'] = AccountGroup::deriveNormalBalance($request->nature);
        $data['is_system'] = false; // Only system seeds can create system groups
        
        if (!isset($data['is_active'])) {
            $data['is_active'] = false;
        }

        AccountGroup::create($data);

        return redirect()->route('account-groups.index')->with('success', 'Ledger Group created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AccountGroup $accountGroup)
    {
        $accountGroup->load('parent', 'children');
        $accountGroup->loadCount('ledgers');
        
        return view('admin.account-groups.show', compact('accountGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccountGroup $accountGroup)
    {
        $parentGroups = AccountGroup::where('id', '!=', $accountGroup->id)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get();
        $rootNatures = ['Assets', 'Liabilities', 'Income', 'Expenses', 'Capital'];

        return view('admin.account-groups.edit', compact('accountGroup', 'parentGroups', 'rootNatures'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccountGroup $accountGroup)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:account_groups,name,' . $accountGroup->id,
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];

        // System groups have restricted editing
        if (!$accountGroup->is_system) {
            $rules['parent_id'] = 'nullable|exists:account_groups,id';
            $rules['nature'] = 'required|in:Assets,Liabilities,Income,Expenses,Capital';
        }

        $request->validate($rules);

        $data = $request->all();
        
        if (!isset($data['is_active'])) {
            $data['is_active'] = false;
        }

        if (!$accountGroup->is_system) {
            // Check for circular reference
            if ($request->parent_id && $accountGroup->createsCircularReference($request->parent_id)) {
                return back()->withInput()->withErrors(['parent_id' => 'Cannot assign this parent as it would create a circular reference.']);
            }

            // Ensure child inherits nature from parent if parent is provided
            if ($request->parent_id) {
                $parent = AccountGroup::find($request->parent_id);
                if ($parent->nature !== $request->nature) {
                    return back()->withInput()->withErrors(['nature' => 'The accounting nature must match the parent group\'s nature (' . $parent->nature . ').']);
                }
            }

            $data['normal_balance'] = AccountGroup::deriveNormalBalance($request->nature);
        } else {
            // Cannot modify structure of system groups
            unset($data['parent_id'], $data['nature']);
            // If the group is essential, we probably shouldn't even let them deactivate it if ledgers depend on it, 
            // but we'll allow deactivation if no ledgers for now.
        }

        $accountGroup->update($data);

        return redirect()->route('account-groups.index')->with('success', 'Ledger Group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccountGroup $accountGroup)
    {
        if ($accountGroup->is_system) {
            return back()->with('error', 'Cannot delete a system-defined Ledger Group.');
        }

        if ($accountGroup->children()->count() > 0) {
            return back()->with('error', 'Cannot delete this group because it contains child groups.');
        }

        if ($accountGroup->ledgers()->count() > 0) {
            return back()->with('error', 'Cannot delete this group because it is assigned to existing ledgers.');
        }

        $accountGroup->delete();

        return redirect()->route('account-groups.index')->with('success', 'Ledger Group deleted successfully.');
    }
}
