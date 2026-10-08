<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $groupId = null;
        if (!empty($row['group'])) {
            $group = ProductGroup::firstOrCreate(['name' => trim($row['group'])]);
            $groupId = $group->id;
        }

        $subgroupId = null;
        if (!empty($row['subgroup']) && $groupId) {
            $subgroup = ProductSubgroup::firstOrCreate([
                'name' => trim($row['subgroup']),
                'product_group_id' => $groupId
            ]);
            $subgroupId = $subgroup->id;
        }

        return new Product([
            'part_code'           => $row['part_code'],
            'item_name'           => $row['item_name'],
            'product_group_id'    => $groupId,
            'product_subgroup_id' => $subgroupId,
            'unit'                => !empty($row['unit']) ? $row['unit'] : 'Nos',
            'lp_price'            => isset($row['lp_price']) && $row['lp_price'] !== '' ? $row['lp_price'] : 0,
            'gst_rate'            => isset($row['gst_rate']) && $row['gst_rate'] !== '' ? $row['gst_rate'] : 0,
            'hsn_code'            => $row['hsn_code'] ?? null,
            'minimum_stock'       => isset($row['minimum_stock']) && $row['minimum_stock'] !== '' ? $row['minimum_stock'] : 0,
            'description'         => $row['description'] ?? null,
            'status'              => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'part_code' => 'required|unique:products,part_code',
            'item_name' => 'required',
        ];
    }
}
