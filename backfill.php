<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dispatches = \App\Models\SalesDispatch::with('items.saleItem.product')->get();
foreach($dispatches as $dispatch) {
    if (\App\Models\Invoice::where('invoice_number', str_replace('DN-', 'INV-', $dispatch->dispatch_number))->exists()) {
        continue;
    }
    
    $invoice = \App\Models\Invoice::create([
        'sale_id' => $dispatch->sale_id,
        'invoice_number' => str_replace('DN-', 'INV-', $dispatch->dispatch_number),
        'invoice_date' => $dispatch->dispatch_date,
        'total_amount' => 0,
        'status' => 'Sent',
        'notes' => 'Generated from Dispatch ' . $dispatch->dispatch_number,
    ]);
    
    $invoiceTotal = 0;
    foreach($dispatch->items as $item) {
        if ($item->saleItem) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item->product_id,
                'quantity' => $item->dispatched_qty,
                'unit_price' => $item->saleItem->unit_price,
                'total_price' => $item->dispatched_qty * $item->saleItem->unit_price,
            ]);
            $invoiceTotal += $item->dispatched_qty * $item->saleItem->unit_price;
        }
    }
    $invoice->total_amount = $invoiceTotal;
    $invoice->save();
}
echo "Invoices backfilled.\n";
