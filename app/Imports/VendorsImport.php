<?php

namespace App\Imports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class VendorsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        return new Vendor([
            'vendor_code'    => $row['vendor_code'] ?? null,
            'company_name'   => $row['company_name'],
            'contact_person' => $row['contact_person'] ?? null,
            'mobile'         => $row['mobile'] ?? '',
            'email'          => $row['email'] ?? null,
            'gst_no'         => $row['gst_no'] ?? null,
            'lead_time_days' => isset($row['lead_time_days']) && $row['lead_time_days'] !== '' ? $row['lead_time_days'] : 0,
            'status'         => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|unique:vendors,company_name',
        ];
    }
}
