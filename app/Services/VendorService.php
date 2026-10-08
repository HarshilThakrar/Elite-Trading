<?php

namespace App\Services;

use App\Repositories\VendorRepositoryInterface;

use App\Models\Ledger;
use App\Models\PurchasePlanItem;
use Illuminate\Support\Facades\DB;

class VendorService extends BaseService
{
    protected $vendorRepository;

    public function __construct(VendorRepositoryInterface $vendorRepository)
    {
        $this->vendorRepository = $vendorRepository;
    }

    public function getAllVendors()
    {
        return $this->vendorRepository->all();
    }

    public function createVendor(array $data)
    {
        if (empty($data['vendor_code'])) {
            $data['vendor_code'] = 'VEND-' . strtoupper(uniqid());
        }

        $vendor = $this->vendorRepository->create($data);

        // Auto-create Ledger for Vendor
        $creditorsGroup = \App\Models\AccountGroup::where('name', 'Sundry Creditors')->first();
        if ($creditorsGroup) {
            \App\Models\Ledger::create([
                'name' => $vendor->company_name,
                'account_group_id' => $creditorsGroup->id,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_type' => 'Cr', // Creditors have Credit opening balance by default
                'is_system' => false,
                'type' => 'vendor',
                'reference_id' => $vendor->id,
            ]);
        }

        return $vendor;
    }

    public function getVendorById($id)
    {
        return $this->vendorRepository->find($id);
    }

    public function updateVendor($id, array $data)
    {
        return $this->vendorRepository->update($id, $data);
    }

    public function deleteVendor($id)
    {
        $vendor = $this->getVendorById($id);

        $hasPurchases = $vendor->purchases()->exists();
        $hasPurchasePlans = PurchasePlanItem::where('suggested_vendor_id', $vendor->id)->exists();
        $ledger = Ledger::where('type', 'vendor')->where('reference_id', $vendor->id)->first();
        $hasTransactions = $ledger && $ledger->entries()->exists();

        // If vendor has linked records, cannot hard-delete without breaking DB constraints/audit
        if ($hasPurchases || $hasPurchasePlans || $hasTransactions) {
            $alreadyInactive = !$vendor->status;
            $vendor->update(['status' => false]);
            if ($ledger) {
                $ledger->update(['is_active' => false]);
            }

            return [
                'status' => 'deactivated',
                'already_inactive' => $alreadyInactive,
                'vendor' => $vendor,
                'hasPurchases' => $hasPurchases,
                'hasTransactions' => $hasTransactions,
            ];
        }

        // Clean hard-delete
        DB::transaction(function () use ($vendor, $ledger) {
            if ($ledger) {
                $ledger->delete();
            }
            $vendor->delete();
        });

        return [
            'status' => 'deleted',
            'vendor' => $vendor,
        ];
    }
}
