<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesVoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::where('type', 'Sales')->with(['reference', 'entries.ledger']);

        if ($request->has('voucher_number') && $request->voucher_number) {
            $query->where('voucher_number', 'like', '%' . $request->voucher_number . '%');
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $vouchers = $query->orderBy('date', 'desc')->paginate(15);
        return view('admin.sales-vouchers.index', compact('vouchers'));
    }

    public function show(Voucher $sales_voucher)
    {
        if ($sales_voucher->type !== 'Sales') {
            abort(404);
        }
        $sales_voucher->load(['entries.ledger', 'reference.sale.customer', 'reference.items.product']);
        return view('admin.sales-vouchers.show', compact('sales_voucher'));
    }

    public function generatePdf(Voucher $sales_voucher)
    {
        if ($sales_voucher->type !== 'Sales') {
            abort(404);
        }
        $sales_voucher->load(['entries.ledger', 'reference.sale.customer']);
        
        $pdf = Pdf::loadView('admin.sales-vouchers.pdf', compact('sales_voucher'));
        return $pdf->download('Sales_Voucher_' . $sales_voucher->voucher_number . '.pdf');
    }
}
