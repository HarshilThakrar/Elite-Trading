<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_ledger_id')->constrained('ledgers')->onDelete('cascade');
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('description', 500);
            $table->string('reference_number', 100)->nullable();
            
            // Normalized amounts from the bank's perspective
            // e.g. bank debit = money left the account (Withdrawal)
            // bank credit = money entered the account (Deposit)
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2)->nullable();
            
            $table->string('reconciliation_status', 50)->default('Unreconciled'); // Unreconciled, Reconciled, Ignored
            
            $table->foreignId('matched_journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('import_hash')->unique(); // For duplicate protection
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index('transaction_date');
            $table->index('reference_number');
            $table->index('reconciliation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_transactions');
    }
};
