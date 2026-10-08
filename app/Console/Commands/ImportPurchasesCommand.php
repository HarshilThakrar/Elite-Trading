<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\AccountGroup;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportPurchasesCommand extends Command
{
    protected $signature = 'import:purchases';
    protected $description = 'Import purchases, vendors, and line items from all purchase Excel files';

    public function handle()
    {
        $files = [
            'PURCHASE APRIL.xlsx',
            'MAY PURCHASE.xlsx',
            'JUNE PURCHASE.xlsx',
            'JULY PURCHASE.xlsx',
            'AUG. PURCHASE.xlsx',
        ];

        $creditorsGroup = AccountGroup::firstOrCreate(
            ['name' => 'Sundry Creditors'],
            ['nature' => 'Liabilities']
        );

        $vendorCodeCounter = 6001;

        $totalPurchasesImported = 0;
        $totalItemsImported = 0;

        foreach ($files as $file) {
            $filePath = base_path($file);
            if (!file_exists($filePath)) {
                $this->warn("File not found: $file");
                continue;
            }

            $this->info("Importing $file...");

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName('Purchase Register');
            if (!$sheet) {
                // Fallback to active sheet
                $sheet = $spreadsheet->getActiveSheet();
            }

            $rows = $sheet->toArray();
            $filePurchases = 0;
            $fileItems = 0;

            $currentPurchase = null;
            $headerQty = 0;
            $headerTotal = 0;
            $headerTaxable = 0;

            foreach ($rows as $r => $cells) {
                if ($r < 8) continue; // Skip header section

                // Check if this row is a purchase header:
                // Date in col 0, Particulars in col 1, Voucher type 'Purchase' in col 6
                $hasDate = !empty($cells[0]);
                $hasVoucherType = isset($cells[6]) && strtolower(trim((string)$cells[6])) === 'purchase';

                if ($hasDate && $hasVoucherType) {
                    // Check if previous purchase had 0 items, if so create a fallback item
                    if ($currentPurchase && $currentPurchase->items()->count() === 0 && ($headerTotal > 0 || $headerTaxable > 0)) {
                        $this->createFallbackItem($currentPurchase, $headerQty, $headerTaxable > 0 ? $headerTaxable : $headerTotal);
                        $fileItems++;
                        $totalItemsImported++;
                    }

                    // Parse Date
                    $rawDate = $cells[0];
                    if (is_numeric($rawDate)) {
                        $poDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } else {
                        $poDate = date('Y-m-d', strtotime(str_replace('/', '-', $rawDate)));
                    }

                    $vendorName = trim((string)($cells[1] ?? 'Unknown Vendor'));
                    $voucherNo = trim((string)($cells[7] ?? ''));
                    $refNo = trim((string)($cells[8] ?? ''));
                    $poNumber = !empty($refNo) ? $refNo : (!empty($voucherNo) ? 'VCH-' . $voucherNo : 'PO-' . time() . '-' . rand(100, 999));
                    
                    $gstin = trim((string)($cells[10] ?? ''));
                    $narration = trim((string)($cells[11] ?? ''));

                    // Parse Amounts
                    $rawTotal = (string)($cells[17] ?? '0');
                    $totalAmount = (float) preg_replace('/[^0-9.]/', '', $rawTotal);

                    // Taxable in col 24 or 18
                    $rawTaxable = (string)($cells[24] ?? $cells[18] ?? '0');
                    $headerTaxable = (float) preg_replace('/[^0-9.]/', '', $rawTaxable);

                    $rawQty = (string)($cells[14] ?? '0');
                    $headerQty = (float) preg_replace('/[^0-9.]/', '', $rawQty);
                    $headerTotal = $totalAmount;

                    // Vendor handling:
                    // Specifically if SHAKUNTAL PRINTERS, assign V-6018!
                    $vendor = Vendor::where('company_name', $vendorName)->first();
                    if (!$vendor) {
                        if (stripos($vendorName, 'SHAKUNTAL') !== false) {
                            $code = 'V-6018';
                        } else {
                            if ($vendorCodeCounter === 6018) {
                                $vendorCodeCounter++; // skip 6018 for Shakuntal
                            }
                            $code = 'V-' . $vendorCodeCounter++;
                        }

                        $vendor = Vendor::create([
                            'vendor_code' => $code,
                            'company_name' => $vendorName,
                            'contact_person' => $vendorName,
                            'mobile' => '9898000000',
                            'email' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($vendorName, 0, 10))) . '@vendor.com',
                            'gst_no' => $gstin ?: null,
                            'status' => true,
                        ]);

                        // Auto-create ledger
                        Ledger::firstOrCreate(
                            ['name' => $vendor->company_name, 'reference_id' => $vendor->id, 'type' => 'vendor'],
                            [
                                'account_group_id' => $creditorsGroup->id,
                                'opening_balance' => 0,
                                'opening_balance_type' => 'Cr',
                                'is_system' => false,
                            ]
                        );
                    } else {
                        // If vendor exists and is Shakuntal, ensure code is V-6018
                        if (stripos($vendorName, 'SHAKUNTAL') !== false && $vendor->vendor_code !== 'V-6018') {
                            $vendor->vendor_code = 'V-6018';
                            $vendor->save();
                        }
                    }

                    // Create or find Purchase
                    $currentPurchase = Purchase::firstOrCreate(
                        ['po_number' => $poNumber],
                        [
                            'vendor_id' => $vendor->id,
                            'po_date' => $poDate,
                            'total_amount' => $totalAmount,
                            'status' => 'Approved',
                            'notes' => $narration ?: ("Voucher No: $voucherNo, Imported from $file"),
                        ]
                    );

                    $filePurchases++;
                    $totalPurchasesImported++;

                } elseif (empty($cells[0]) && !empty($cells[1]) && $currentPurchase) {
                    // Child item row
                    $itemName = trim((string)$cells[1]);
                    $rawQty = (string)($cells[14] ?? '0');
                    $qty = (float) preg_replace('/[^0-9.]/', '', $rawQty);
                    
                    $rawValue = (string)($cells[15] ?? '0');
                    $value = (float) preg_replace('/[^0-9.]/', '', $rawValue);

                    if ($qty > 0 && $value > 0) {
                        $unitPrice = round($value / $qty, 2);

                        // Find or create product
                        $product = Product::where('item_name', $itemName)->first();
                        if (!$product) {
                            $product = Product::create([
                                'part_code' => 'P-' . rand(10000, 99999),
                                'item_name' => $itemName,
                                'unit' => 'NOS',
                                'status' => 1,
                                'available_stock' => 0,
                                'gst_rate' => 18,
                            ]);
                        }

                        PurchaseItem::create([
                            'purchase_id' => $currentPurchase->id,
                            'product_id' => $product->id,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'total_price' => $value,
                            'invoiced_qty' => 0,
                        ]);

                        $fileItems++;
                        $totalItemsImported++;
                    }
                }
            }

            // Check if last purchase of the file had 0 items
            if ($currentPurchase && $currentPurchase->items()->count() === 0 && ($headerTotal > 0 || $headerTaxable > 0)) {
                $this->createFallbackItem($currentPurchase, $headerQty, $headerTaxable > 0 ? $headerTaxable : $headerTotal);
                $fileItems++;
                $totalItemsImported++;
            }

            $this->info("Imported $filePurchases purchases and $fileItems items from $file.");
        }

        $this->info("=========================================");
        $this->info("ALL PURCHASES & VENDORS IMPORTED SUCCESSFULLY!");
        $this->info("Total Purchases: $totalPurchasesImported");
        $this->info("Total Items: $totalItemsImported");
        $this->info("Total Vendors: " . Vendor::count());
        
        $v6018 = Vendor::where('vendor_code', 'V-6018')->first();
        if ($v6018) {
            $this->info("Vendor V-6018: {$v6018->company_name} (ID: {$v6018->id})");
            $this->info("POs for V-6018: " . $v6018->purchases()->count());
        }
        $this->info("=========================================");
    }

    private function createFallbackItem(Purchase $purchase, $qty, $amount)
    {
        $vendor = $purchase->vendor;
        $vendorName = $vendor ? $vendor->company_name : 'General';
        
        $itemName = stripos($vendorName, 'SHAKUNTAL') !== false
            ? 'Printing & Stationery Services (SHAKUNTAL PRINTERS)'
            : "General Purchase / Services ($vendorName)";

        $product = Product::firstOrCreate(
            ['item_name' => $itemName],
            [
                'part_code' => 'GEN-' . ($vendor ? $vendor->vendor_code : 'PURCHASE'),
                'unit' => 'NOS',
                'status' => 1,
                'available_stock' => 0,
                'gst_rate' => 18,
            ]
        );

        $q = $qty > 0 ? $qty : 1;
        $unitPrice = round($amount / $q, 2);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $q,
            'unit_price' => $unitPrice,
            'total_price' => $amount,
            'invoiced_qty' => 0,
        ]);
    }
}
