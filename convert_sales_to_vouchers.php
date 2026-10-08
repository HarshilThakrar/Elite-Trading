<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = \App\Models\Sale::whereDate('created_at', date('Y-m-d'))->get();
$count = 0;
$finYear = \App\Models\FinancialYear::first();
if (!$finYear) {
    $finYear = \App\Models\FinancialYear::create([
        'name' => '2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'is_active' => true,
    ]);
}

foreach ($sales as $sale) {
    // Check if voucher exists
    $exists = \App\Models\Voucher::where('reference_id', $sale->id)
        ->where('reference_type', \App\Models\Sale::class)
        ->exists();
    if (!$exists) {
        $v = \App\Models\Voucher::create([
            'voucher_number' => $sale->invoice_number,
            'date' => $sale->sale_date,
            'type' => 'Sales',
            'narration' => 'Imported Sales Voucher',
            'financial_year_id' => $finYear->id,
            'reference_id' => $sale->id,
            'reference_type' => \App\Models\Sale::class,
            'created_by' => 1,
            'status' => 'Posted'
        ]);
        
        // Ensure Sales A/c ledger exists
        $salesLedger = \App\Models\Ledger::where('name', 'Sales A/c')->first();
        if ($salesLedger) {
            \App\Models\JournalEntry::create([
                'voucher_id' => $v->id,
                'ledger_id' => $salesLedger->id,
                'type' => 'Cr',
                'amount' => $sale->total_amount,
                'narration' => 'Sales import'
            ]);
        }
        
        $count++;
    }
}

echo "Created $count vouchers.";
