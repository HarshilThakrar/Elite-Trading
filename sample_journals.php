<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sheets = Maatwebsite\Excel\Facades\Excel::toArray(new class implements Maatwebsite\Excel\Concerns\ToArray {
    public function array(array $array) {}
}, 'JOURNAL REGISTER.xlsx');

if (isset($sheets[5])) {
    echo json_encode(array_slice($sheets[5], 0, 15), JSON_PRETTY_PRINT);
}
