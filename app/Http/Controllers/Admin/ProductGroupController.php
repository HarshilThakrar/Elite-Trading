<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = \App\Models\ProductGroup::all();
        return view('admin.product_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.product_groups.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:product_groups']);
        \App\Models\ProductGroup::create($request->all());
        return redirect()->route('product-groups.index')->with('success', 'Product Group created successfully.');
    }

    public function edit(string $id)
    {
        $group = \App\Models\ProductGroup::findOrFail($id);
        return view('admin.product_groups.edit', compact('group'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate(['name' => 'required|string|max:255|unique:product_groups,name,' . $id]);
        $group = \App\Models\ProductGroup::findOrFail($id);
        $group->update($request->all());
        return redirect()->route('product-groups.index')->with('success', 'Product Group updated successfully.');
    }

    public function destroy(string $id)
    {
        $group = \App\Models\ProductGroup::findOrFail($id);
        
        if ($group->subgroups()->count() > 0) {
            return redirect()->route('product-groups.index')->with('error', 'Cannot delete this Product Group because it has associated subgroups.');
        }
        
        if (\App\Models\Product::where('product_group_id', $group->id)->count() > 0) {
            return redirect()->route('product-groups.index')->with('error', 'Cannot delete this Product Group because it has associated products.');
        }

        $group->delete();
        return redirect()->route('product-groups.index')->with('success', 'Product Group deleted successfully.');
    }
}
