<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray
{
    public function array(array $array)
    {
    }
}, 'SALES APRIL.xlsx');
foreach ($sheets as $index => $sheet) {
    echo "Sheet " . $index . "\n";
    if (count($sheet) > 0) {
        echo json_encode(array_slice($sheet[0], 0, 15)) . "\n";
        if (count($sheet) > 1) {
            echo json_encode(array_slice($sheet[1], 0, 15)) . "\n";
        }
    }
}