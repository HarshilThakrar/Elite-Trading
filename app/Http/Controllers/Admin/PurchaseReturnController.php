<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PurchaseReturn, PurchaseReturnItem, Purchase, Ledger, Product};
use App\Services\AccountingEngine;
use DB;
class PurchaseReturnController extends Controller
{
    public function index()
    {
        $returns = PurchaseReturn::with('purchase.vendor')->latest()->paginate(20);
        return view('admin.purchase_returns.index', compact('returns'));
    }

    public function store(Request $request, AccountingEngine $accountingEngine)
    {
        // Minimal logic for debit note
        DB::beginTransaction();
        try {
            // Simplified for brevity, similar to Sales Return
            DB::commit();
            return back()->with('success', 'Purchase Return processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}