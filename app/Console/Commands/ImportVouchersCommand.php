<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Voucher;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportVouchersCommand extends Command
{
    protected $signature = 'import:vouchers';
    protected $description = 'Import receipt and contra vouchers from Excel files';

    public function handle()
    {
        $this->importReceipts('RECEIPTS APRIL TO AUG 2026.xlsx');
        $this->importContras('CONTRA 01.04.2026 TO 31.08.2026.xlsx');
        $this->info("All imports completed!");
    }

    private function importReceipts($filename)
    {
        if (!file_exists(base_path($filename))) {
            $this->error("File not found: $filename");
            return;
        }

        $this->info("Importing $filename...");
        
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, base_path($filename));

        // Looking for the sheet with Receipts, which is usually sheet 4
        $sheet = $sheets[4] ?? null;
        if (!$sheet) {
            $this->error("Target sheet not found in $filename");
            return;
        }

        $count = 0;
        foreach ($sheet as $row) {
            $typeIdx = 6;
            $vchNoIdx = 7;
            
            if (isset($row[$typeIdx]) && $row[$typeIdx] === 'Receipt') {
                $vchNo = $row[$vchNoIdx];
                if (empty($vchNo)) continue;
                $vchNo = 'RCT-' . $vchNo;

                $excelDate = $row[0];
                $date = is_numeric($excelDate) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d') : date('Y-m-d', strtotime($excelDate));
                $amount = (float)($row[9] ?? $row[8] ?? 0);
                $partyName = $row[1] ?? 'Unknown';

                $finYearId = \App\Models\FinancialYear::firstOrCreate(
                    ['name' => '2025-2026'],
                    ['start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_active' => true]
                )->id;
                $voucher = Voucher::updateOrCreate(
                    [
                        'voucher_number' => $vchNo, 
                        'type' => 'Receipt'
                    ],
                    [
                        'date' => $date,
                        'status' => 'Posted',
                        'financial_year_id' => $finYearId,
                        'metadata' => [
                            'party_name' => $partyName,
                            'amount' => $amount
                        ]
                    ]
                );
                
                // Add Journal Entry if amount exists
                if ($amount > 0 && $voucher->wasRecentlyCreated) {
                    $accGroupId = \App\Models\AccountGroup::firstOrCreate(['name' => 'Imported Group'], ['nature' => 'Assets'])->id;
                    $ledger = Ledger::firstOrCreate(['name' => $partyName], ['type' => 'Asset', 'account_group_id' => $accGroupId]);
                    
                    // Simple single entry for visualization
                    $voucher->entries()->create([
                        'ledger_id' => $ledger->id,
                        'type' => 'Cr',
                        'amount' => $amount,
                        'narration' => 'Imported from Excel'
                    ]);
                }
                
                $count++;
            }
        }
        
        $this->info("Imported $count receipt vouchers.");
    }
    
    private function importContras($filename)
    {
        if (!file_exists(base_path($filename))) {
            $this->error("File not found: $filename");
            return;
        }

        $this->info("Importing $filename...");
        
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, base_path($filename));

        // Contra is usually sheet 3
        $sheet = $sheets[3] ?? null;
        if (!$sheet) {
            $this->error("Target sheet not found in $filename");
            return;
        }

        $count = 0;
        foreach ($sheet as $row) {
            $typeIdx = 4;
            $vchNoIdx = 5;
            
            if (isset($row[$typeIdx]) && $row[$typeIdx] === 'Contra') {
                $vchNo = $row[$vchNoIdx];
                if (empty($vchNo)) continue;
                $vchNo = 'CNT-' . $vchNo;

                $excelDate = $row[0];
                $date = is_numeric($excelDate) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d') : date('Y-m-d', strtotime($excelDate));
                $amount = (float)($row[7] ?? $row[6] ?? 0);
                $partyName = $row[1] ?? 'Unknown';

                $finYearId = \App\Models\FinancialYear::firstOrCreate(
                    ['name' => '2025-2026'],
                    ['start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_active' => true]
                )->id;
                $voucher = Voucher::updateOrCreate(
                    [
                        'voucher_number' => $vchNo, 
                        'type' => 'Contra'
                    ],
                    [
                        'date' => $date,
                        'status' => 'Posted',
                        'financial_year_id' => $finYearId,
                        'metadata' => [
                            'party_name' => $partyName,
                            'amount' => $amount
                        ]
                    ]
                );
                
                // Add Journal Entry if amount exists
                if ($amount > 0 && $voucher->wasRecentlyCreated) {
                    $accGroupId = \App\Models\AccountGroup::firstOrCreate(['name' => 'Imported Group'], ['nature' => 'Assets'])->id;
                    $ledger = Ledger::firstOrCreate(['name' => $partyName], ['type' => 'Asset', 'account_group_id' => $accGroupId]);
                    
                    $voucher->entries()->create([
                        'ledger_id' => $ledger->id,
                        'type' => 'Cr',
                        'amount' => $amount,
                        'narration' => 'Imported from Excel'
                    ]);
                }
                
                $count++;
            }
        }
        
        $this->info("Imported $count contra vouchers.");
    }
}
