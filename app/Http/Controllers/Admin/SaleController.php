<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Services\CustomerService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    protected $saleService;
    protected $customerService;
    protected $productService;

    public function __construct(
        SaleService $saleService,
        CustomerService $customerService,
        ProductService $productService
    ) {
        $this->saleService = $saleService;
        $this->customerService = $customerService;
        $this->productService = $productService;
    }

    public function index()
    {
        $sales = $this->saleService->getAllSales();
        return view('admin.sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = $this->customerService->getAllCustomers()->where('status', true);
        $products = $this->productService->getAllProducts()->where('status', true);
        $invoice_number = $this->saleService->generateInvoiceNumber();

        return view('admin.sales.create', compact('customers', 'products', 'invoice_number'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number'    => 'required|unique:sales',
            'customer_id'       => 'required|exists:customers,id',
            'sale_date'         => 'required|date',
            'eway_bill_no'      => 'nullable|string',
            'eway_bill_date'    => 'nullable|date',
            'delivery_note'     => 'nullable|string',
            'mode_of_payment'   => 'nullable|string',
            'reference_no_date' => 'nullable|string',
            'other_references'  => 'nullable|string',
            'buyer_order_no'    => 'nullable|string',
            'buyer_order_date'  => 'nullable|date',
            'dispatched_through'=> 'nullable|string',
            'destination'       => 'nullable|string',
            'terms_of_delivery' => 'nullable|string',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit_price'=> 'required|numeric|min:0',
        ]);

        // Calculate total amount and check stock
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            if ($product && $product->available_stock < $item['quantity']) {
                return redirect()->back()->withInput()->with('error', 'Not enough stock for ' . $product->item_name . '. Available: ' . $product->available_stock);
            }
            $total_amount += ($item['quantity'] * $item['unit_price']);
        }
        $data['total_amount'] = $total_amount;

        try {
            $sale = $this->saleService->createSale($data);
            return redirect()->route('sales.show', $sale->id)->with('success', 'Sales Order created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $sale = $this->saleService->getSaleById($id);
        return view('admin.sales.show', compact('sale'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Dispatched,Cancelled'
        ]);

        try {
            $this->saleService->updateSaleStatus($id, $request->status);
            return redirect()->route('sales.show', $id)->with('success', 'Sales Order status updated to ' . $request->status);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function generatePdf($id, Request $request)
    {
        try {
            $sale = $this->saleService->getSaleById($id);
            if (!$sale) {
                return redirect()->route('sales.index')->with('error', 'Sale record not found.');
            }

            $sale->loadMissing(['customer', 'items.product']);
            $is_einvoice = $request->input('type') == 'einvoice';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sales.pdf', compact('sale', 'is_einvoice'));
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            // Sanitize filename for Windows & browser compatibility (remove forward slashes)
            $safeNumber = str_replace(['/', '\\', ' '], ['-', '-', '_'], $sale->invoice_number ?? (string)$sale->id);
            $filename = 'Sales_Order_' . $safeNumber . '.pdf';

            if ($request->has('preview') || $request->has('view')) {
                return $pdf->stream($filename);
            }

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Sales PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'PDF generate karne me issue aaya: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->saleService->deleteSale($id);
            return redirect()->route('sales.index')->with('success', 'Sales Order deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('sales.index')->with('error', $e->getMessage());
        }
    }
}

