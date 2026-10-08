<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Customer::orderBy('id', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Customer Code',
            'Company Name',
            'Contact Person',
            'Mobile',
            'Email',
            'GST No',
            'Address',
            'City',
            'State',
            'Pincode',
            'Credit Limit',
            'Payment Terms',
            'Status'
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->customer_code,
            $customer->company_name,
            $customer->contact_person ?? '',
            $customer->mobile,
            $customer->email ?? '',
            $customer->gst_no ?? '',
            $customer->address ?? '',
            $customer->city ?? '',
            $customer->state ?? '',
            $customer->pincode ?? '',
            $customer->credit_limit ? (float)$customer->credit_limit : 0,
            $customer->payment_terms ?? '',
            $customer->status ? 'Active' : 'Inactive',
        ];
    }
}
