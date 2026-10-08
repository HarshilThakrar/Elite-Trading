<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function gstReport()
    {
        return view('admin.compliance.gst-report');
    }

    public function gstr1()
    {
        return view('admin.compliance.gstr1');
    }

    public function gstr3b()
    {
        return view('admin.compliance.gstr3b');
    }

    public function eInvoice()
    {
        return view('admin.compliance.e-invoice');
    }

    public function eWayBill()
    {
        return view('admin.compliance.e-way-bill');
    }
}
