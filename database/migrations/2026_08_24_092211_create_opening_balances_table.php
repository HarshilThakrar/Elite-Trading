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
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('cascade');
            $table->foreignId('ledger_id')->constrained('ledgers')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('type', ['Dr', 'Cr']);
            $table->date('opening_date');
            $table->string('narration', 500)->nullable();
            $table->string('reference', 100)->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Ensure only ONE opening balance per ledger per financial year
            $table->unique(['financial_year_id', 'ledger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
