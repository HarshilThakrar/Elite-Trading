<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Carbon\Carbon;

$filename = $argv[1] ?? 'PURCHASE APRIL.xlsx';
if (!file_exists($filename)) {
    die("File not found: $filename\n");
}
$spreadsheet = IOFactory::load($filename);
$worksheet = $spreadsheet->getActiveSheet();

$currentPurchase = null;
$purchasesCreated = 0;
$itemsCreated = 0;

$r = 0;
foreach ($worksheet->getRowIterator() as $row) {
    $r++;
    if ($r < 10) continue; // skip headers
    
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    $cells = [];
    foreach ($cellIterator as $cell) {
        $cells[] = $cell->getValue();
    }
    
    // Check if header row or item row
    if (!empty($cells[0]) && is_numeric($cells[0]) && isset($cells[6]) && strtolower(trim($cells[6])) === 'purchase') {
        // It's a purchase header
        $excelDate = $cells[0];
        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d');
        $vendorName = trim($cells[1]);
        $voucherNo = trim($cells[7]);
        $referenceNo = trim($cells[8]);
        $poNumber = !empty($referenceNo) ? $referenceNo : $voucherNo;
        if (empty($poNumber)) {
            $poNumber = 'PO-' . time() . '-' . rand(100, 999);
        }
        $gstin = trim($cells[10]);
        $totalAmount = (float) $cells[17];
        
        // Find or create vendor
        $vendor = Vendor::where('company_name', $vendorName)->first();
        if (!$vendor) {
            $vendor = Vendor::create([
                'vendor_code' => 'V-' . rand(1000, 9999),
                'company_name' => $vendorName,
                'contact_person' => 'Default Contact',
                'mobile' => '0000000000',
                'email' => 'default@example.com',
                'gst_no' => $gstin,
                'status' => 1,
            ]);
        }
        
        // Create Purchase
        $currentPurchase = Purchase::firstOrCreate(
            ['po_number' => $poNumber],
            [
                'vendor_id' => $vendor->id,
                'po_date' => $date,
                'total_amount' => $totalAmount,
                'status' => 'Received',
                'notes' => 'Imported from Excel',
            ]
        );
        $purchasesCreated++;
    } elseif (empty($cells[0]) && !empty($cells[1]) && $currentPurchase) {
        // It's an item row
        $itemName = trim($cells[1]);
        $qty = (float) $cells[14];
        $totalPrice = (float) $cells[15];
        
        if ($qty <= 0) continue;
        
        $unitPrice = $totalPrice / $qty;
        
        // Find or create product
        $product = Product::where('item_name', $itemName)->first();
        if (!$product) {
            $product = Product::create([
                'part_code' => 'P-' . rand(10000, 99999),
                'item_name' => $itemName,
                'status' => 1,
                'unit' => 'NOS',
            ]);
        }
        
        // Create Purchase Item
        PurchaseItem::create([
            'purchase_id' => $currentPurchase->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);
        $itemsCreated++;
    }
}

echo "Import complete!\n";
echo "Purchases created: $purchasesCreated\n";
echo "Items created: $itemsCreated\n";
