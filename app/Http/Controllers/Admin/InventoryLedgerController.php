<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryLedger;
use App\Models\Product;

class InventoryLedgerController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        $query = InventoryLedger::with('product')->orderBy('id', 'desc');

        if ($request->has('product_id') && $request->product_id != '') {
            $query->where('product_id', $request->product_id);
        }

        $ledgers = $query->paginate(50);

        return view('admin.inventory.ledger.index', compact('ledgers', 'products'));
    }
}
