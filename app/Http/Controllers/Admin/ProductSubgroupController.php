<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductSubgroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subgroups = \App\Models\ProductSubgroup::with('group')->get();
        return view('admin.product_subgroups.index', compact('subgroups'));
    }

    public function create()
    {
        $groups = \App\Models\ProductGroup::all();
        return view('admin.product_subgroups.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_group_id' => 'required|exists:product_groups,id',
            'name' => 'required|string|max:255'
        ]);
        \App\Models\ProductSubgroup::create($request->all());
        return redirect()->route('product-subgroups.index')->with('success', 'Product Sub Group created successfully.');
    }

    public function edit(string $id)
    {
        $subgroup = \App\Models\ProductSubgroup::findOrFail($id);
        $groups = \App\Models\ProductGroup::all();
        return view('admin.product_subgroups.edit', compact('subgroup', 'groups'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'product_group_id' => 'required|exists:product_groups,id',
            'name' => 'required|string|max:255'
        ]);
        $subgroup = \App\Models\ProductSubgroup::findOrFail($id);
        $subgroup->update($request->all());
        return redirect()->route('product-subgroups.index')->with('success', 'Product Sub Group updated successfully.');
    }

    public function destroy(string $id)
    {
        $subgroup = \App\Models\ProductSubgroup::findOrFail($id);

        if (\App\Models\Product::where('product_subgroup_id', $subgroup->id)->count() > 0) {
            return redirect()->route('product-subgroups.index')->with('error', 'Cannot delete this Product Subgroup because it has associated products.');
        }

        $subgroup->delete();
        return redirect()->route('product-subgroups.index')->with('success', 'Product Sub Group deleted successfully.');
    }
}
