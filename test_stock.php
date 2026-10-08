<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $sale = App\Models\Sale::with(['items.product'])->find(1);
    $physicalStock = $sale->items[0]->product->available_stock + $sale->items[0]->product->reserved_stock;
    echo "Stock: " . $physicalStock . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
