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

        $sales_voucher->load(['entries.ledger', 'reference']);

        if ($sales_voucher->reference) {
            if ($sales_voucher->reference instanceof \App\Models\Invoice) {
                $sales_voucher->reference->loadMissing(['sale.customer', 'items.product']);
            } elseif ($sales_voucher->reference instanceof \App\Models\Sale) {
                $sales_voucher->reference->loadMissing(['customer', 'items.product']);
            }
        }

        return view('admin.sales-vouchers.show', compact('sales_voucher'));
    }

    public function generatePdf(Voucher $sales_voucher, Request $request)
    {
        if ($sales_voucher->type !== 'Sales') {
            abort(404);
        }

        $sales_voucher->load(['entries.ledger', 'reference']);

        if ($sales_voucher->reference) {
            if ($sales_voucher->reference instanceof \App\Models\Invoice) {
                $sales_voucher->reference->loadMissing(['sale.customer']);
            } elseif ($sales_voucher->reference instanceof \App\Models\Sale) {
                $sales_voucher->reference->loadMissing(['customer']);
            }
        }

        $pdf = Pdf::loadView('admin.sales-vouchers.pdf', compact('sales_voucher'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans'
            ]);

        $safeVch = str_replace(['/', '\\', ' '], ['-', '-', '_'], $sales_voucher->voucher_number ?? (string)$sales_voucher->id);
        $filename = 'Sales_Voucher_' . $safeVch . '.pdf';

        if ($request->has('preview') || $request->has('view')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }
}
