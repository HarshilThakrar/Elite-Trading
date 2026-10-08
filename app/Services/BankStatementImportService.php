<?php

namespace App\Services;

use App\Models\BankStatementTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class BankStatementImportService
{
    /**
     * Parse CSV file and map columns dynamically based on user mapping.
     */
    public function importMappedData($ledgerId, $filePath, $mapping)
    {
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Ignore empty rows
                if (empty(array_filter($data))) continue;

                $rowData = [];
                foreach ($mapping as $dbField => $csvColumnIndex) {
                    if ($csvColumnIndex !== null && $csvColumnIndex !== '') {
                        $rowData[$dbField] = trim($data[(int)$csvColumnIndex] ?? '');
                    } else {
                        $rowData[$dbField] = null;
                    }
                }
                $rows[] = $rowData;
            }
            fclose($handle);
        }

        $results = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'error_messages' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try {
                    $txnDate = $this->parseDate($row['transaction_date']);
                    if (!$txnDate) {
                        throw new Exception("Invalid transaction date.");
                    }

                    $valDate = $this->parseDate($row['value_date'] ?? null) ?: $txnDate;
                    
                    $debit = $this->parseAmount($row['debit_amount'] ?? 0);
                    $credit = $this->parseAmount($row['credit_amount'] ?? 0);
                    $balance = isset($row['running_balance']) && $row['running_balance'] !== '' ? $this->parseAmount($row['running_balance']) : null;
                    
                    if ($debit == 0 && $credit == 0) {
                        // Sometimes there's just an 'amount' column and positive/negative denotes direction
                        if (isset($row['amount']) && $row['amount'] !== '') {
                            $amt = $this->parseAmount($row['amount']);
                            if ($amt < 0) {
                                $debit = abs($amt);
                            } else {
                                $credit = $amt;
                            }
                        } else {
                            throw new Exception("Row has no debit or credit amount.");
                        }
                    }

                    $desc = mb_substr($row['description'] ?? '', 0, 500);
                    $ref = mb_substr($row['reference_number'] ?? '', 0, 100);

                    // Generate Hash: LedgerID + Date + Desc + Ref + Debit + Credit
                    $hashString = $ledgerId . '|' . $txnDate->format('Y-m-d') . '|' . strtolower($desc) . '|' . strtolower($ref) . '|' . number_format($debit, 2) . '|' . number_format($credit, 2);
                    $hash = md5($hashString);

                    // Check duplicate
                    if (BankStatementTransaction::where('import_hash', $hash)->exists()) {
                        $results['duplicates']++;
                        continue; // Skip duplicate
                    }

                    BankStatementTransaction::create([
                        'bank_ledger_id' => $ledgerId,
                        'transaction_date' => $txnDate,
                        'value_date' => $valDate,
                        'description' => $desc,
                        'reference_number' => $ref,
                        'debit_amount' => $debit,
                        'credit_amount' => $credit,
                        'running_balance' => $balance,
                        'reconciliation_status' => 'Unreconciled',
                        'import_hash' => $hash,
                        'metadata' => [
                            'original_row' => $row,
                            'imported_at' => now()->toDateTimeString()
                        ]
                    ]);

                    $results['imported']++;
                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_messages'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    private function parseDate($dateStr)
    {
        if (empty($dateStr)) return null;
        try {
            // Try standard formats
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAmount($amountStr)
    {
        if (empty($amountStr)) return 0;
        // Remove commas and currency symbols
        $clean = preg_replace('/[^0-9\.\-]/', '', $amountStr);
        return (float) $clean;
    }
}
