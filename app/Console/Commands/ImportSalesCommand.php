<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Sale;
use App\Models\Customer;
use Carbon\Carbon;

class ImportSalesCommand extends Command
{
    protected $signature = 'import:sales';
    protected $description = 'Import sales from Excel files';

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
            if (!file_exists(base_path($file))) {
                $this->error("File not found: $file");
                continue;
            }

            $this->info("Importing $file...");
            
            $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, base_path($file));

            if (!isset($sheets[1])) {
                $this->error("Sheet 1 not found in $file");
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
            $buyerIdx = array_search('Buyer', $headers);
            $vchNoIdx = array_search('Voucher No.', $headers);
            $totalIdx = array_search('Gross Total', $headers);

            if ($dateIdx === false || $buyerIdx === false || $vchNoIdx === false || $totalIdx === false) {
                $this->error("Missing required columns in $file");
                continue;
            }

            $count = 0;
            for ($i = $dataStartIndex; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $vchNo = $row[$vchNoIdx];
                if (empty($vchNo)) continue;

                $buyerName = $row[$buyerIdx] ?? 'Unknown';
                $customer = Customer::firstOrCreate(['company_name' => $buyerName], [
                    'customer_code' => 'CUST-' . strtoupper(substr(md5($buyerName), 0, 6)),
                    'contact_person' => $buyerName,
                    'mobile' => '0000000000',
                    'status' => 1
                ]);

                $excelDate = $row[$dateIdx];
                $saleDate = is_numeric($excelDate) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d') : date('Y-m-d', strtotime($excelDate));

                Sale::updateOrCreate(
                    ['invoice_number' => $vchNo],
                    [
                        'customer_id' => $customer->id,
                        'sale_date' => $saleDate,
                        'total_amount' => (float)$row[$totalIdx],
                        'status' => 'Approved',
                    ]
                );
                $count++;
            }
            $this->info("Imported $count sales from $file");
        }
        
        $this->info("All imports completed!");
    }
}
