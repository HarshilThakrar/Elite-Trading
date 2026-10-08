<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class ImportSalesCommand extends Command
{
    protected $signature = 'import:sales';
    protected $description = 'Import sales and their line items from Excel files';

    public function handle()
    {
        $files = [
            'SALES APRIL.xlsx',
            'SALES MAY.xlsx',
            'SALES JUNE.xlsx',
            'SALES JULY.xlsx',
            'SALES AUGUST.xlsx',
        ];

        foreach ($files as $file) {
            $filePath = base_path($file);
            if (!file_exists($filePath)) {
                $this->error("File not found: $file");
                continue;
            }

            $this->info("Importing $file...");
            
            $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, $filePath);

            if (!isset($sheets[1])) {
                $this->error("Sheet 1 (Sales Register) not found in $file");
                continue;
            }

            $sheet = $sheets[1];
            $headers = null;
            $dataStartIndex = 0;

            for ($i = 0; $i < min(15, count($sheet)); $i++) {
                $nonNull = array_filter($sheet[$i], function($v) { return $v !== null && $v !== ''; });
                if (count($nonNull) > 5 && in_array('Voucher No.', $sheet[$i])) {
                    $headers = $sheet[$i];
                    $dataStartIndex = $i + 1;
                    break;
                }
            }

            if (!$headers) {
                $this->error("Could not find headers in $file");
                continue;
            }

            $dateIdx = array_search('Date', $headers);
            $particularsIdx = array_search('Particulars', $headers);
            $buyerIdx = array_search('Buyer', $headers);
            $vchNoIdx = array_search('Voucher No.', $headers);
            $qtyIdx = array_search('Quantity', $headers);
            $valueIdx = array_search('Value', $headers);
            $totalIdx = array_search('Gross Total', $headers);

            if ($dateIdx === false || $buyerIdx === false || $vchNoIdx === false || $totalIdx === false) {
                $this->error("Missing required columns in $file");
                continue;
            }

            $count = 0;
            $totalItemsImported = 0;
            $currentSale = null;
            $currentProduct = null;
            $headerQty = 0;
            $headerTotal = 0;

            for ($i = $dataStartIndex; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $vchNo = !empty($row[$vchNoIdx]) ? trim((string)$row[$vchNoIdx]) : null;

                if (!empty($vchNo)) {
                    // Check if previous sale had 0 items, create fallback item if needed
                    if ($currentSale && $currentSale->items()->count() === 0 && ($headerQty > 0 || $headerTotal > 0)) {
                        $fallbackProduct = Product::firstOrCreate(
                            ['item_name' => 'General Trading Item'],
                            [
                                'part_code' => 'P-GENERAL',
                                'unit' => 'NOS',
                                'status' => 1,
                                'available_stock' => 0
                            ]
                        );
                        $qty = $headerQty > 0 ? $headerQty : 1;
                        $rate = $headerTotal > 0 ? ($headerTotal / $qty) : 0;
                        SaleItem::create([
                            'sale_id' => $currentSale->id,
                            'product_id' => $fallbackProduct->id,
                            'quantity' => $qty,
                            'unit_price' => $rate,
                            'total_price' => $headerTotal,
                        ]);
                        $totalItemsImported++;
                    }

                    $buyerName = !empty($row[$buyerIdx]) ? trim((string)$row[$buyerIdx]) : 'Unknown';
                    $customer = Customer::firstOrCreate(['company_name' => $buyerName], [
                        'customer_code' => 'CUST-' . strtoupper(substr(md5($buyerName), 0, 6)),
                        'contact_person' => $buyerName,
                        'mobile' => '0000000000',
                        'status' => 1
                    ]);

                    $excelDate = $row[$dateIdx];
                    $saleDate = is_numeric($excelDate) 
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d') 
                        : date('Y-m-d', strtotime($excelDate));

                    $headerTotal = (float)($row[$totalIdx] ?? 0);
                    $headerQty = (isset($row[$qtyIdx]) && is_numeric($row[$qtyIdx])) ? (float)$row[$qtyIdx] : 0;

                    $currentSale = Sale::updateOrCreate(
                        ['invoice_number' => $vchNo],
                        [
                            'customer_id' => $customer->id,
                            'sale_date' => $saleDate,
                            'total_amount' => $headerTotal,
                            'status' => 'Approved',
                        ]
                    );

                    // Delete existing items for clean re-import
                    $currentSale->items()->delete();

                    $count++;
                    $currentProduct = null;
                } elseif ($currentSale) {
                    $particulars = !empty($row[$particularsIdx]) ? trim((string)$row[$particularsIdx]) : null;
                    $rowQty = (isset($row[$qtyIdx]) && is_numeric($row[$qtyIdx])) ? (float)$row[$qtyIdx] : null;
                    $rowVal = (isset($row[$valueIdx]) && is_numeric($row[$valueIdx])) ? (float)$row[$valueIdx] : null;

                    if (!empty($particulars) && $rowQty !== null && $rowQty > 0) {
                        $unitPrice = ($rowVal !== null && $rowVal > 0) ? ($rowVal / $rowQty) : 0;
                        $totalPrice = $rowVal ?? ($rowQty * $unitPrice);

                        // Find or create product
                        $product = Product::firstOrCreate(
                            ['item_name' => $particulars],
                            [
                                'part_code' => 'P-' . strtoupper(substr(md5($particulars), 0, 8)),
                                'unit' => 'NOS',
                                'lp_price' => $unitPrice,
                                'status' => 1,
                                'available_stock' => 0
                            ]
                        );

                        SaleItem::create([
                            'sale_id' => $currentSale->id,
                            'product_id' => $product->id,
                            'quantity' => $rowQty,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                        ]);

                        $totalItemsImported++;
                        $currentProduct = $product;
                    } elseif (!empty($particulars) && $currentProduct && ($rowQty === null || $rowQty == 0)) {
                        // Secondary line: e.g. catalog/part code or description
                        if (empty($currentProduct->description) || $currentProduct->description === $currentProduct->item_name) {
                            $currentProduct->update(['description' => $particulars]);
                        }
                    }
                }
            }

            // Check last sale of file
            if ($currentSale && $currentSale->items()->count() === 0 && ($headerQty > 0 || $headerTotal > 0)) {
                $fallbackProduct = Product::firstOrCreate(
                    ['item_name' => 'General Trading Item'],
                    [
                        'part_code' => 'P-GENERAL',
                        'unit' => 'NOS',
                        'status' => 1,
                        'available_stock' => 0
                    ]
                );
                $qty = $headerQty > 0 ? $headerQty : 1;
                $rate = $headerTotal > 0 ? ($headerTotal / $qty) : 0;
                SaleItem::create([
                    'sale_id' => $currentSale->id,
                    'product_id' => $fallbackProduct->id,
                    'quantity' => $qty,
                    'unit_price' => $rate,
                    'total_price' => $headerTotal,
                ]);
                $totalItemsImported++;
            }

            $this->info("Imported $count sales and $totalItemsImported items from $file");
        }
        
        $this->info("All sales & items imported successfully!");
    }
}
