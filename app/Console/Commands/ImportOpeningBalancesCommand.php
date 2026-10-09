<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use App\Models\OpeningBalance;
use App\Models\FinancialYear;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Str;

class ImportOpeningBalancesCommand extends Command
{
    protected $signature = 'import:opening-balances {file=OP. BALANCE.xlsx}';
    protected $description = 'Import opening balances from Excel file';

    public function handle()
    {
        $filePath = base_path($this->argument('file'));
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $activeYear = FinancialYear::where('is_active', 1)->first();
        if (!$activeYear) {
            $this->error("No active financial year found.");
            return 1;
        }

        $this->info("Loading Excel file...");
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $currentLedgerName = null;
        $count = 0;

        foreach ($rows as $index => $row) {
            $col0 = trim((string)($row[0] ?? ''));
            
            // Check for new ledger
            if ($col0 === 'Ledger:') {
                $currentLedgerName = trim((string)($row[1] ?? ''));
                continue;
            }

            // Check for Opening Balance row
            $col2 = trim((string)($row[2] ?? ''));
            if ($currentLedgerName && $col2 === 'Opening Balance') {
                $debit = floatval(str_replace(',', '', (string)($row[5] ?? '0')));
                $credit = floatval(str_replace(',', '', (string)($row[6] ?? '0')));

                $amount = 0;
                $type = 'Dr';

                if ($debit > 0) {
                    $amount = $debit;
                    $type = 'Dr';
                } elseif ($credit > 0) {
                    $amount = $credit;
                    $type = 'Cr';
                }

                if ($amount > 0) {
                    $ledger = Ledger::where('name', $currentLedgerName)->first();
                    if (!$ledger) {
                        $ledger = Ledger::create([
                            'name' => $currentLedgerName,
                            'account_group_id' => 1, // Default group
                            'is_active' => true,
                            'is_system' => false,
                        ]);
                    }

                    // Update or create OpeningBalance
                    OpeningBalance::updateOrCreate(
                        [
                            'financial_year_id' => $activeYear->id,
                            'ledger_id' => $ledger->id,
                        ],
                        [
                            'amount' => $amount,
                            'type' => $type,
                            'opening_date' => $activeYear->start_date,
                            'narration' => 'Imported Opening Balance',
                        ]
                    );

                    // Also update Ledger directly
                    $ledger->update([
                        'opening_balance' => $amount,
                        'opening_balance_type' => $type,
                        'opening_balance_date' => $activeYear->start_date,
                    ]);

                    $this->line("Imported: {$currentLedgerName} -> {$amount} {$type}");
                    $count++;
                }

                $currentLedgerName = null; // Reset until next Ledger
            }
        }

        $this->info("Import completed! Total records imported: {$count}");
        return 0;
    }
}
