<?php

namespace App\Services;

use App\Repositories\PurchaseRepositoryInterface;
use App\Repositories\PurchaseItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseService extends BaseService
{
    protected $purchaseRepository;
    protected $purchaseItemRepository;
    protected $inventoryService;

    public function __construct(
        PurchaseRepositoryInterface $purchaseRepository,
        PurchaseItemRepositoryInterface $purchaseItemRepository
    ) {
        $this->purchaseRepository = $purchaseRepository;
        $this->purchaseItemRepository = $purchaseItemRepository;
    }

    public function getAllPurchases()
    {
        return $this->purchaseRepository->all()->load(['vendor']);
    }

    public function getPurchaseById($id)
    {
        return $this->purchaseRepository->find($id)->load(['vendor', 'items.product']);
    }

    public function createPurchase(array $data)
    {
        return DB::transaction(function () use ($data) {
            $purchase = $this->purchaseRepository->create([
                'po_number'    => $data['po_number'],
                'vendor_id'    => $data['vendor_id'],
                
                'po_date'      => $data['po_date'],
                'total_amount' => $data['total_amount'],
                'status'       => 'Draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $this->purchaseItemRepository->create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $purchase;
        });
    }

    public function updatePurchaseStatus($id, $status)
    {
        return DB::transaction(function () use ($id, $status) {
            $purchase = $this->getPurchaseById($id);

            // If it is already received, don't receive again
            if ($purchase->status === 'Received') {
                throw new \Exception("This Purchase Order is already received.");
            }

            $purchase->status = $status;
            $purchase->save();

            return $purchase;
        });
    }

    public function generatePoNumber()
    {
        $lastPurchase = \App\Models\Purchase::orderBy('id', 'desc')->first();
        $nextId = $lastPurchase ? $lastPurchase->id + 1 : 1;
        return 'PO-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
