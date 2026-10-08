<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function printRows($file) {
    echo "Rows for $file:\n";
    $sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
        public function array(array $array) {}
    }, base_path($file));
    
    foreach ($sheets as $sheetIndex => $sheet) {
        if ($sheetIndex == 0 || $sheetIndex == 1) continue; // Skip first two sheets
        echo "Sheet $sheetIndex:\n";
        $printed = 0;
        foreach($sheet as $row) {
            $nonNull = array_filter($row, function($v) { return $v !== null && $v !== ''; });
            if (count($nonNull) > 4) {
                print_r($row);
                $printed++;
                if($printed > 5) break;
            }
        }
    }
}

printRows('RECEIPTS APRIL TO AUG 2026.xlsx');
printRows('CONTRA 01.04.2026 TO 31.08.2026.xlsx');
