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
        $customer = $this->getCustomerById($id);
        if (!$customer) {
            return [
                'status' => 'not_found',
                'customer' => null,
            ];
        }

        $hasSales = $customer->sales()->exists();
        $hasQuotations = $customer->quotations()->exists();
        $ledger = \App\Models\Ledger::where('type', 'customer')->where('reference_id', $customer->id)->first();
        $hasTransactions = $ledger && $ledger->entries()->exists();

        // If customer has linked records, cannot hard-delete without breaking DB constraints/audit
        if ($hasSales || $hasQuotations || $hasTransactions) {
            $alreadyInactive = !$customer->status;
            $customer->update(['status' => false]);
            if ($ledger) {
                $ledger->update(['is_active' => false]);
            }

            return [
                'status' => 'deactivated',
                'already_inactive' => $alreadyInactive,
                'customer' => $customer,
                'hasSales' => $hasSales,
                'hasQuotations' => $hasQuotations,
                'hasTransactions' => $hasTransactions,
            ];
        }

        // Clean hard-delete
        \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $ledger) {
            if ($ledger) {
                $ledger->delete();
            }
            $customer->delete();
        });

        return [
            'status' => 'deleted',
            'customer' => $customer,
        ];
    }
}
