<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchasesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Purchase::with('vendor')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'PO Number',
            'Vendor Name',
            'PO Date',
            'Total Amount',
            'Status'
        ];
    }

    public function map($purchase): array
    {
        return [
            $purchase->id,
            $purchase->po_number,
            $purchase->vendor ? $purchase->vendor->company_name : 'N/A',
            $purchase->po_date,
            $purchase->total_amount,
            $purchase->status
        ];
    }
}
