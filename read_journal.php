<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
    public function array(array $array) {}
}, 'JOURNAL REGISTER.xlsx');
$sheetNames = array_keys($sheets);
echo json_encode($sheetNames);
