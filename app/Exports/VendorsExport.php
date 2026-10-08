<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VendorsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Vendor::orderBy('id', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Vendor Code',
            'Company Name',
            'Contact Person',
            'Mobile',
            'Email',
            'GST No',
            'Lead Time (Days)',
            'Status'
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->id,
            $vendor->vendor_code,
            $vendor->company_name,
            $vendor->contact_person ?? '',
            $vendor->mobile ?? '',
            $vendor->email ?? '',
            $vendor->gst_no ?? '',
            $vendor->lead_time_days ?? 0,
            $vendor->status ? 'Active' : 'Inactive',
        ];
    }
}
