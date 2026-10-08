<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$groups = \App\Models\AccountGroup::where('name', 'like', '%Cash%')->orWhere('name', 'like', '%Bank%')->get();
foreach($groups as $g) {
    echo $g->name . ' -> ' . $g->id . "\n";
}
