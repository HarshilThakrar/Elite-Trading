<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Voucher;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportJournalsCommand extends Command
{
    protected $signature = 'import:journals';
    protected $description = 'Import journal vouchers from Excel files';

    public function handle()
    {
        $this->importJournals('JOURNAL REGISTER.xlsx');
        $this->info("All imports completed!");
    }

    private function importJournals($filename)
    {
        if (!file_exists(base_path($filename))) {
            $this->error("File not found: $filename");
            return;
        }

        $this->info("Importing $filename...");
        
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, base_path($filename));

        // Looking for the sheet with Journals, which is sheet 5
        $sheet = $sheets[5] ?? null;
        if (!$sheet) {
            $this->error("Target sheet not found in $filename");
            return;
        }

        $count = 0;
        foreach ($sheet as $rowIndex => $row) {
            $typeIdx = 3;
            $vchNoIdx = 4;
            
            if (isset($row[$typeIdx]) && $row[$typeIdx] === 'Journal') {
                $vchNo = $row[$vchNoIdx];
                if (empty($vchNo)) continue;
                $vchNo = 'JRN-' . $vchNo;

                $excelDate = $row[0];
                $date = is_numeric($excelDate) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d') : date('Y-m-d', strtotime($excelDate));
                $amount = (float)($row[7] ?? 0); // Gross Total column
                $partyName = $row[1] ?? 'Unknown';

                $finYearId = \App\Models\FinancialYear::firstOrCreate(
                    ['name' => '2025-2026'],
                    ['start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_active' => true]
                )->id;
                
                $voucher = Voucher::updateOrCreate(
                    [
                        'voucher_number' => $vchNo, 
                        'type' => 'Journal'
                    ],
                    [
                        'date' => $date,
                        'status' => 'Posted',
                        'narration' => $row[6] ?? '',
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
                    
                    // Simple single entry for visualization based on existing code structure
                    $voucher->entries()->create([
                        'ledger_id' => $ledger->id,
                        'type' => 'Cr',
                        'amount' => $amount,
                        'narration' => $row[6] ?? 'Imported from Excel'
                    ]);
                }
                
                $count++;
            }
        }
        
        $this->info("Imported $count journal vouchers.");
    }
}
