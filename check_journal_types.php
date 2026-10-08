<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
    public function array(array $array) {}
}, 'JOURNAL REGISTER.xlsx');

$voucherTypes = [];
$firstRowHeaders = [];

foreach ($sheets as $index => $sheet) {
    if (empty($sheet)) continue;
    
    $headers = $sheet[0];
    if (empty($firstRowHeaders)) {
        $firstRowHeaders = $headers;
    }
    
    // Find index of 'Voucher Type'
    $typeIndex = array_search('Voucher Type', $headers);
    if ($typeIndex === false) {
        $typeIndex = 3; // fallback
    }
    
    foreach ($sheet as $rowIndex => $row) {
        if ($rowIndex === 0) continue; // skip header
        if (isset($row[$typeIndex])) {
            $voucherTypes[$row[$typeIndex]] = true;
        }
    }
}

echo "Headers: \n";
print_r($firstRowHeaders);
echo "\nVoucher Types: \n";
print_r(array_keys($voucherTypes));
