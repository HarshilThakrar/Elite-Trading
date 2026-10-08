<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        return new Customer([
            'customer_code'  => $row['customer_code'] ?? null,
            'company_name'   => $row['company_name'],
            'contact_person' => $row['contact_person'] ?? null,
            'mobile'         => $row['mobile'] ?? '',
            'email'          => $row['email'] ?? null,
            'gst_no'         => $row['gst_no'] ?? null,
            'address'        => $row['address'] ?? null,
            'city'           => $row['city'] ?? null,
            'state'          => $row['state'] ?? null,
            'pincode'        => $row['pincode'] ?? null,
            'credit_limit'   => isset($row['credit_limit']) && $row['credit_limit'] !== '' ? $row['credit_limit'] : 0,
            'payment_terms'  => $row['payment_terms'] ?? null,
            'status'         => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|unique:customers,company_name',
        ];
    }
}

