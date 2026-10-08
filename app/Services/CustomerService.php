<?php

namespace App\Services;

use App\Repositories\CustomerRepositoryInterface;

class CustomerService extends BaseService
{
    protected $customerRepository;

    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function getAllCustomers()
    {
        return $this->customerRepository->all();
    }

    public function createCustomer(array $data)
    {
        // Example: Auto-generate customer code if not provided
        if (empty($data['customer_code'])) {
            $data['customer_code'] = 'CUST-' . strtoupper(uniqid());
        }

        $customer = $this->customerRepository->create($data);
        
        // Auto-create Ledger for Customer
        $debtorsGroup = \App\Models\AccountGroup::where('name', 'Sundry Debtors')->first();
        if ($debtorsGroup) {
            \App\Models\Ledger::create([
                'name' => $customer->company_name,
                'account_group_id' => $debtorsGroup->id,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_type' => 'Dr',
                'is_system' => false,
                'type' => 'customer',
                'reference_id' => $customer->id,
            ]);
        }

        return $customer;
    }

    public function getCustomerById($id)
    {
        return $this->customerRepository->find($id);
    }

    public function updateCustomer($id, array $data)
    {
        return $this->customerRepository->update($id, $data);
    }

    public function deleteCustomer($id)
    {
        return $this->customerRepository->delete($id);
    }
}
