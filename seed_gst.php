<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$group = \App\Models\AccountGroup::where('name', 'Duties & Taxes')->first();
if ($group) {
    \App\Models\Ledger::firstOrCreate(['name' => 'CGST A/c', 'account_group_id' => $group->id], ['is_system' => true]);
    \App\Models\Ledger::firstOrCreate(['name' => 'SGST A/c', 'account_group_id' => $group->id], ['is_system' => true]);
    \App\Models\Ledger::firstOrCreate(['name' => 'IGST A/c', 'account_group_id' => $group->id], ['is_system' => true]);
    echo "GST Ledgers Seeded.\n";
} else {
    echo "Duties & Taxes group not found.\n";
}
