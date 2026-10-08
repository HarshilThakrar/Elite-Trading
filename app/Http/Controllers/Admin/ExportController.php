<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use App\Exports\CustomersExport;
use App\Exports\ProductsExport;
use App\Exports\PurchasesExport;

class ExportController extends Controller
{
    public function index()
    {
        return view('admin.export-sharing.index');
    }

    public function exportSales()
    {
        return Excel::download(new SalesExport, 'sales_report_' . date('Y_m_d') . '.xlsx');
    }

    public function exportCustomers()
    {
        return Excel::download(new CustomersExport, 'customers_list_' . date('Y_m_d') . '.xlsx');
    }

    public function exportProducts()
    {
        return Excel::download(new ProductsExport, 'products_inventory_' . date('Y_m_d') . '.xlsx');
    }

    public function exportPurchases()
    {
        return Excel::download(new PurchasesExport, 'purchases_report_' . date('Y_m_d') . '.xlsx');
    }
}
