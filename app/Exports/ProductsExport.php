<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Part Code',
            'Product Name',
            'HSN Code',
            'UOM',
            'List Price',
            'Stock Quantity',
            'ABC Category'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->part_code,
            $product->name,
            $product->hsn_code,
            $product->uom,
            $product->list_price,
            $product->stock_quantity,
            $product->abc_category
        ];
    }
}
