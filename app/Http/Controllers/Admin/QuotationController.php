<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\LpHistory;
use App\Services\QuotationService;
use App\Http\Requests\StoreQuotationRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuotationExport;

class QuotationController extends Controller
{
    protected $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    public function index()
    {
        $quotations = Quotation::with(['customer', 'creator'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        return view('admin.quotations.create', compact('customers', 'products'));
    }

    public function store(StoreQuotationRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            $isAdmin = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
            $requiresApproval = !$isAdmin;

            $status = $requiresApproval ? 'Pending Approval' : 'Draft';

            $quotation = Quotation::create([
                'quotation_number' => $this->quotationService->generateQuotationNumber(),
                'customer_id' => $validated['customer_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $validated['subtotal'],
                'freight_charges' => $validated['freight_charges'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'total_amount' => $validated['grand_total'],
                'gst_amount' => $validated['gst_amount'] ?? 0,
                'grand_total_with_gst' => $validated['grand_total_with_gst'] ?? $validated['grand_total'],
                'status' => $status,
                'notes' => $validated['notes'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Using service to compute rates and profit before saving
                $rates = $this->quotationService->calculateItemRates(
                    $item['list_price'],
                    $item['purchase_discount'],
                    $item['customer_discount'],
                    $item['quantity']
                );

                QuotationItem::create(array_merge([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'list_price' => $item['list_price'],
                    'purchase_discount' => $item['purchase_discount'],
                    'customer_discount' => $item['customer_discount'],
                    'gst_rate' => $item['gst_rate'] ?? 0,
                    'gst_amount' => $item['gst_amount'] ?? 0,
                    'line_total_with_gst' => $item['line_total_with_gst'] ?? 0,
                ], $rates));
                
                // Update LP History only if product currently has no LP
                $product = Product::find($item['product_id']);
                if ($product && (empty($product->lp_price) || (float)$product->lp_price == 0) && (float)$item['list_price'] > 0) {
                    LpHistory::create([
                        'product_id' => $product->id,
                        'old_price'  => $product->lp_price,
                        'new_price'  => $item['list_price'],
                        'user_id'    => auth()->id(),
                    ]);
                    $product->lp_price = $item['list_price'];
                    $product->save();
                }
            }

            DB::commit();

            $admins = \App\Models\User::role(['Super Admin', 'Admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewQuotationNotification($quotation));

            return redirect()->route('quotations.index')->with('success', 'Quotation created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating quotation: ' . $e->getMessage());
        }
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product']);
        return view('admin.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $customers = Customer::all();
        $products = Product::all();
        $quotation->load('items');
        return view('admin.quotations.edit', compact('quotation', 'customers', 'products'));
    }

    public function update(StoreQuotationRequest $request, Quotation $quotation)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            $isAdmin = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
            $requiresApproval = !$isAdmin;

            $updateData = [
                'customer_id' => $validated['customer_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $validated['subtotal'],
                'freight_charges' => $validated['freight_charges'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'total_amount' => $validated['grand_total'],
                'gst_amount' => $validated['gst_amount'] ?? 0,
                'grand_total_with_gst' => $validated['grand_total_with_gst'] ?? $validated['grand_total'],
                'notes' => $validated['notes'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            ];

            if ($requiresApproval) {
                $updateData['status'] = 'Pending Approval';
            }

            $quotation->update($updateData);

            // Clear existing items and re-insert
            $quotation->items()->delete();

            foreach ($validated['items'] as $item) {
                $rates = $this->quotationService->calculateItemRates(
                    $item['list_price'],
                    $item['purchase_discount'],
                    $item['customer_discount'],
                    $item['quantity']
                );

                QuotationItem::create(array_merge([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'list_price' => $item['list_price'],
                    'purchase_discount' => $item['purchase_discount'],
                    'customer_discount' => $item['customer_discount'],
                    'gst_rate' => $item['gst_rate'] ?? 0,
                    'gst_amount' => $item['gst_amount'] ?? 0,
                    'line_total_with_gst' => $item['line_total_with_gst'] ?? 0,
                ], $rates));
                
                // Update LP History only if product currently has no LP
                $product = Product::find($item['product_id']);
                if ($product && (empty($product->lp_price) || (float)$product->lp_price == 0) && (float)$item['list_price'] > 0) {
                    LpHistory::create([
                        'product_id' => $product->id,
                        'old_price'  => $product->lp_price,
                        'new_price'  => $item['list_price'],
                        'user_id'    => auth()->id(),
                    ]);
                    $product->lp_price = $item['list_price'];
                    $product->save();
                }
            }

            DB::commit();

            return redirect()->route('quotations.index')->with('success', 'Quotation updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating quotation: ' . $e->getMessage());
        }
    }

    public function generatePdf(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product']);
        $pdf = Pdf::loadView('quotations.pdf', compact('quotation'));
        return $pdf->download('Quotation_' . $quotation->quotation_number . '.pdf');
    }

    public function export($id, $type)
    {
        $quotation = Quotation::with(['customer', 'items.product'])->findOrFail($id);

        if ($type === 'excel') {
            return Excel::download(new QuotationExport($id), 'quotation-'.$quotation->quotation_number.'.xlsx');
        } elseif ($type === 'pdf') {
            $pdf = Pdf::loadView('admin.quotations.pdf', ['quotation' => $quotation])
                      ->setPaper('a4', 'landscape');
            return $pdf->download('quotation-internal-'.$quotation->quotation_number.'.pdf');
        } elseif ($type === 'image') {
            return view('quotations.pdf', ['quotation' => $quotation, 'forImage' => true]);
        }

        abort(404);
    }

    public function changeStatus(Request $request, Quotation $quotation)
    {
        $request->validate(['status' => 'required|in:Draft,Approved,Sent,Accepted,Rejected,Pending Approval']);
        $quotation->update(['status' => $request->status]);
        return back()->with('success', 'Status updated successfully');
    }

    public function approvals()
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Unauthorized');
        }
        $quotations = Quotation::with(['customer', 'creator'])
            ->where('status', 'Pending Approval')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return view('admin.quotations.approvals', compact('quotations'));
    }

    public function approve(Quotation $quotation)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Unauthorized');
        }
        $quotation->update(['status' => 'Approved']);
        return back()->with('success', 'Quotation approved successfully.');
    }

    public function updateCustomerStatus(Request $request, Quotation $quotation)
    {
        $request->validate(['customer_status' => 'required|in:Pending,Accepted,Rejected,Negotiating']);
        $quotation->update(['customer_status' => $request->customer_status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Customer status updated successfully'
        ]);
    }

    public function convertToSale(Request $request, Quotation $quotation)
    {
        try {
            if ($quotation->valid_until && \Carbon\Carbon::parse($quotation->valid_until)->isPast()) {
                throw new \Exception("Cannot convert an expired quotation to a Sales Order.");
            }

            DB::beginTransaction();
            
            // Accept the quotation
            $quotation->update([
                'status' => 'Approved',
                'customer_status' => 'Accepted'
            ]);

            // Create Sales Order via Service
            $saleService = app(\App\Services\SaleService::class);
            $invoice_number = $saleService->generateInvoiceNumber();
            
            $saleData = [
                'invoice_number' => $invoice_number,
                'customer_id'    => $quotation->customer_id,
                'sale_date'      => now()->format('Y-m-d'),
                'total_amount'   => $quotation->grand_total,
                'notes'          => "Converted from Quotation: " . $quotation->quotation_number,
                'items'          => []
            ];
            
            foreach ($quotation->items as $item) {
                $saleData['items'][] = [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->customer_rate ?? $item->unit_price,
                ];
            }

            $sale = $saleService->createSale($saleData);
            
            DB::commit();

            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Quotation successfully converted to Sales Order.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error converting to Sales Order: ' . $e->getMessage());
        }
    }
}
