<?php

namespace App\Services;

use App\Repositories\SaleRepositoryInterface;
use App\Repositories\SaleItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SaleService extends BaseService
{
    protected $saleRepository;
    protected $saleItemRepository;
    protected $inventoryService;

    public function __construct(
        SaleRepositoryInterface $saleRepository,
        SaleItemRepositoryInterface $saleItemRepository
    ) {
        $this->saleRepository = $saleRepository;
        $this->saleItemRepository = $saleItemRepository;
    }

    public function getAllSales()
    {
        return $this->saleRepository->all()->load(['customer']);
    }

    public function getSaleById($id)
    {
        return $this->saleRepository->find($id)->load(['customer', 'items.product']);
    }

    public function createSale(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sale = $this->saleRepository->create([
                'invoice_number' => $data['invoice_number'],
                'customer_id'    => $data['customer_id'],
                
                'sale_date'      => $data['sale_date'],
                'total_amount'   => $data['total_amount'],
                'status'         => 'Draft',
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $this->saleItemRepository->create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);

                $product = \App\Models\Product::find($item['product_id']);
                if ($product && (float)$product->lp_price !== (float)$item['unit_price']) {
                    \App\Models\LpHistory::create([
                        'product_id' => $product->id,
                        'old_price'  => $product->lp_price,
                        'new_price'  => $item['unit_price'],
                        'user_id'    => auth()->id(),
                    ]);
                    $product->lp_price = $item['unit_price'];
                    $product->save();
                }
            }

            return $sale;
        });
    }

    public function updateSaleStatus($id, $status)
    {
        return DB::transaction(function () use ($id, $status) {
            $sale = $this->getSaleById($id);
            $oldStatus = $sale->status;

            // If it is already Dispatched, don't dispatch again
            if ($sale->status === 'Dispatched') {
                throw new \Exception("This Sales Order is already dispatched.");
            }

            // Handle Reservation when Approved
            if ($status === 'Approved' && $oldStatus !== 'Approved') {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->available_stock -= $item->quantity;
                        $product->reserved_stock += $item->quantity;
                        $product->save();
                    }
                }
            }

            // Handle Release when Cancelled
            if ($status === 'Cancelled' && $oldStatus === 'Approved') {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->available_stock += $item->quantity;
                        $product->reserved_stock -= $item->quantity;
                        $product->save();
                    }
                }
            }

            // If status is changed to Dispatched
            if ($status === 'Dispatched') {
                // Now handled via DispatchController/SalesDispatch
            }

            $sale->status = $status;
            $sale->save();

            return $sale;
        });
    }

    public function deleteSale($id)
    {
        return DB::transaction(function () use ($id) {
            $sale = $this->getSaleById($id);
            
            if ($sale->status === 'Dispatched') {
                throw new \Exception("Cannot delete a dispatched sales order.");
            }

            if ($sale->status === 'Approved') {
                // Revert reserved stock
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->available_stock += $item->quantity;
                        $product->reserved_stock -= $item->quantity;
                        $product->save();
                    }
                }
            }
            
            $sale->items()->delete();
            return $sale->delete();
        });
    }

    public function generateInvoiceNumber()
    {
        $lastSale = \App\Models\Sale::orderBy('id', 'desc')->first();
        $nextId = $lastSale ? $lastSale->id + 1 : 1;
        $invoice = 'SO-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        while (\App\Models\Sale::where('invoice_number', $invoice)->exists()) {
            $nextId++;
            $invoice = 'SO-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }
        return $invoice;
    }
}
