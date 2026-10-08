<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\Services\VendorService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected $purchaseService;
    protected $vendorService;
    protected $productService;

    public function __construct(
        PurchaseService $purchaseService,
        VendorService $vendorService,
        ProductService $productService
    ) {
        $this->purchaseService = $purchaseService;
        $this->vendorService = $vendorService;
        $this->productService = $productService;
    }

    public function index()
    {
        $purchases = $this->purchaseService->getAllPurchases();
        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $vendors = $this->vendorService->getAllVendors()->where('status', true);
        $products = $this->productService->getAllProducts()->where('status', true);
        $po_number = $this->purchaseService->generatePoNumber();

        return view('admin.purchases.create', compact('vendors', 'products', 'po_number'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'po_number'         => 'required|unique:purchases',
            'vendor_id'         => 'required|exists:vendors,id',
            'po_date'           => 'required|date',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit_price'=> 'required|numeric|min:0',
        ]);

        // Calculate total amount
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $total_amount += ($item['quantity'] * $item['unit_price']);
        }
        $data['total_amount'] = $total_amount;

        try {
            $purchase = $this->purchaseService->createPurchase($data);
            return redirect()->route('purchases.show', $purchase->id)->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        return view('admin.purchases.show', compact('purchase'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Received,Cancelled'
        ]);

        try {
            $this->purchaseService->updatePurchaseStatus($id, $request->status);
            return redirect()->route('purchases.show', $id)->with('success', 'Purchase Order status updated to ' . $request->status);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function generatePdf($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.purchases.pdf', compact('purchase'));
        return $pdf->download('Purchase_Order_' . $purchase->po_number . '.pdf');
    }

    public function generateGrnPdf($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        
        if ($purchase->status !== 'Received') {
            return redirect()->back()->with('error', 'Cannot generate GRN. PO is not marked as Received.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.purchases.grn_pdf', compact('purchase'));
        return $pdf->download('GRN_' . $purchase->po_number . '.pdf');
    }

    public function reorder($id)
    {
        try {
            $oldPurchase = $this->purchaseService->getPurchaseById($id);
            
            $data = [
                'po_number' => $this->purchaseService->generatePoNumber(),
                'vendor_id' => $oldPurchase->vendor_id,
                'po_date' => date('Y-m-d'),
                'notes' => $oldPurchase->notes,
                'items' => [],
                'total_amount' => $oldPurchase->total_amount,
            ];

            foreach ($oldPurchase->items as $item) {
                $data['items'][] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ];
            }

            $purchase = $this->purchaseService->createPurchase($data);
            return redirect()->route('purchases.show', $purchase->id)->with('success', 'Purchase Order cloned successfully as Draft.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error cloning PO: ' . $e->getMessage());
        }
    }
}
