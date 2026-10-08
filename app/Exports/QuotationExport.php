<?php

namespace App\Exports;

use App\Models\Quotation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotationExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $quotation;

    public function __construct($id)
    {
        $this->quotation = Quotation::with(['customer', 'items.product'])->findOrFail($id);
    }

    public function view(): View
    {
        return view('admin.quotations.export_format', [
            'quotation' => $this->quotation,
            'forImage' => false
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as centered
            1    => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
        ];
    }
}
