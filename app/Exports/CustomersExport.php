<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::select('id', 'company_name', 'contact_person', 'email', 'phone', 'city', 'state')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company Name',
            'Contact Person',
            'Email',
            'Phone',
            'City',
            'State'
        ];
    }
}
