<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('PURCHASE APRIL.xlsx');
$worksheet = $spreadsheet->getActiveSheet();
$rows = [];
$r = 0;
foreach ($worksheet->getRowIterator() as $row) {
    $r++;
    if ($r < 10) continue; // skip first 9 rows
    
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    $cells = [];
    $isEmpty = true;
    foreach ($cellIterator as $cell) {
        $val = $cell->getValue();
        $cells[] = $val;
        if (!empty($val)) $isEmpty = false;
    }
    if (!$isEmpty) {
        $rows[] = $cells;
    }
    
    if (count($rows) >= 20) break;
}
print_r($rows);
