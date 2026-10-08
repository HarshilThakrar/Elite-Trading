<?php

namespace App\Services;

use App\Repositories\VendorRepositoryInterface;

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
        return $this->vendorRepository->delete($id);
    }
}
