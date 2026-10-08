<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Sale::with('customer')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Invoice Number',
            'Customer Name',
            'Sale Date',
            'Total Amount',
            'Status',
            'Payment Mode'
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->invoice_number,
            $sale->customer ? $sale->customer->company_name : 'N/A',
            $sale->sale_date,
            $sale->total_amount,
            $sale->status,
            $sale->mode_of_payment
        ];
    }
}
