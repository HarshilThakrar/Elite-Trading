<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    public function index()
    {
        $grns = GoodsReceiptNote::with('purchase.vendor')->latest()->get();
        return view('admin.purchases.grn.index', compact('grns'));
    }

    public function create($purchaseId)
    {
        $purchase = Purchase::with(['vendor', 'items.product'])->findOrFail($purchaseId);
        
        if ($purchase->status !== 'Approved') {
            return redirect()->route('purchases.show', $purchaseId)->with('error', 'Only Approved POs can generate a GRN.');
        }

        // Calculate pending quantities
        $itemsWithPending = [];
        foreach ($purchase->items as $item) {
            $receivedSoFar = GoodsReceiptNoteItem::where('purchase_item_id', $item->id)->sum('received_qty');
            $pending = $item->quantity - $receivedSoFar;
            
            if ($pending > 0) {
                $item->pending_qty = $pending;
                $item->received_so_far = $receivedSoFar;
                $itemsWithPending[] = $item;
            }
        }

        if (count($itemsWithPending) === 0) {
            // Auto complete if everything is received but status wasn't updated
            $purchase->update(['status' => 'Received']);
            return redirect()->route('purchases.show', $purchaseId)->with('success', 'All items for this PO have already been received.');
        }

        return view('admin.purchases.grn.create', compact('purchase', 'itemsWithPending'));
    }

    public function store(Request $request, $purchaseId)
    {
        $purchase = Purchase::findOrFail($purchaseId);
        
        $request->validate([
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.batch_no' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $purchase) {
                // Generate GRN Number
                $lastGrn = GoodsReceiptNote::orderBy('id', 'desc')->first();
                $nextId = $lastGrn ? $lastGrn->id + 1 : 1;
                $grnNumber = 'GRN-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

                $grn = GoodsReceiptNote::create([
                    'grn_number' => $grnNumber,
                    'purchase_id' => $purchase->id,
                    'received_date' => $request->received_date,
                    'notes' => $request->notes,
                ]);

                $allReceived = true;
                $totalCgst = 0;
                $totalSgst = 0;
                $totalIgst = 0;
                $totalGrnAmount = 0;

                $gstEngine = app(\App\Services\GstEngine::class);
                $isInterState = false; // Hardcoded for demo

                foreach ($request->items as $itemData) {
                    if ($itemData['received_qty'] > 0) {
                        $purchaseItem = \App\Models\PurchaseItem::find($itemData['purchase_item_id']);
                        
                        GoodsReceiptNoteItem::create([
                            'goods_receipt_note_id' => $grn->id,
                            'purchase_item_id' => $purchaseItem->id,
                            'product_id' => $purchaseItem->product_id,
                            'received_qty' => $itemData['received_qty'],
                            'purchase_rate' => $purchaseItem->unit_price,
                            'batch_no' => $itemData['batch_no'],
                        ]);
                        
                        $itemAmount = $itemData['received_qty'] * $purchaseItem->unit_price;
                        $totalGrnAmount += $itemAmount;

                        $product = $purchaseItem->product;
                        
                        if ($product && $product->gst_rate > 0) {
                            $gstCalc = $gstEngine->calculate($itemAmount, $product->gst_rate, $isInterState);
                            $totalCgst += $gstCalc['cgst'];
                            $totalSgst += $gstCalc['sgst'];
                            $totalIgst += $gstCalc['igst'];
                        }
                        if ($product) {
                            $product->available_stock += $itemData['received_qty'];
                            $product->save();

                            $physicalStock = $product->available_stock + $product->reserved_stock;

                            // Log to Inventory Ledger
                            \App\Models\InventoryLedger::create([
                                'product_id' => $product->id,
                                'type' => 'IN',
                                'quantity' => $itemData['received_qty'],
                                'reference_type' => GoodsReceiptNote::class,
                                'reference_id' => $grn->id,
                                'balance_after' => $physicalStock,
                                'notes' => 'Received via GRN ' . $grn->grn_number,
                            ]);
                        }
                    }
                }

                // --- ACCOUNTING HAS BEEN MOVED TO PURCHASE VOUCHER MODULE ---
                // GrnController now only manages physical inventory.
                // ------------------------------

                // Check if PO is completely fulfilled
                foreach ($purchase->items as $poItem) {
                    $totalReceived = GoodsReceiptNoteItem::where('purchase_item_id', $poItem->id)->sum('received_qty');
                    if ($totalReceived < $poItem->quantity) {
                        $allReceived = false;
                        break;
                    }
                }

                if ($allReceived) {
                    $purchase->update(['status' => 'Received']);
                }
            });

            return redirect()->route('purchases.show', $purchase->id)->with('success', 'GRN Created successfully and Inventory updated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $grn = GoodsReceiptNote::with(['purchase.vendor', 'items.product'])->findOrFail($id);
        return view('admin.purchases.grn.show', compact('grn'));
    }
}
