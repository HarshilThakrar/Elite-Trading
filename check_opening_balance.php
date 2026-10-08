<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
    public function array(array $array) {}
}, 'JOURNAL REGISTER.xlsx');

$openingBalances = [];

foreach ($sheets as $sheetIndex => $sheet) {
    if (empty($sheet)) continue;
    foreach ($sheet as $rowIndex => $row) {
        foreach ($row as $colIndex => $cell) {
            if (is_string($cell) && (stripos($cell, 'opening') !== false || stripos($cell, 'balance') !== false)) {
                $openingBalances[] = [
                    'sheet' => $sheetIndex,
                    'row' => $rowIndex,
                    'col' => $colIndex,
                    'value' => $cell
                ];
            }
        }
    }
}
echo json_encode($openingBalances, JSON_PRETTY_PRINT);
