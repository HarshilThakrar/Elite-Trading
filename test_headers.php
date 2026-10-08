<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function getHeaders($file) {
    echo "Headers for $file:\n";
    $sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
        public function array(array $array) {}
    }, base_path($file));
    
    foreach ($sheets as $sheetIndex => $sheet) {
        echo "Sheet $sheetIndex:\n";
        for ($i = 0; $i < min(15, count($sheet)); $i++) {
            $nonNull = array_filter($sheet[$i], function($v) { return $v !== null && $v !== ''; });
            if (count($nonNull) > 3) {
                print_r($sheet[$i]);
                break;
            }
        }
    }
}

getHeaders('RECEIPTS APRIL TO AUG 2026.xlsx');
getHeaders('CONTRA 01.04.2026 TO 31.08.2026.xlsx');
