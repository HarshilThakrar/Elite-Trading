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
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            
            // General
            $table->string('base_currency')->default('INR');
            $table->string('currency_symbol')->default('₹');
            $table->boolean('mandatory_narration')->default(false);
            
            // Rounding & Decimal
            $table->tinyInteger('amount_decimals')->default(2);
            $table->tinyInteger('qty_decimals')->default(2);
            $table->tinyInteger('rate_decimals')->default(2);
            $table->enum('rounding_method', ['Standard', 'Up', 'Down'])->default('Standard');
            
            // Posting Rules
            $table->boolean('allow_backdated')->default(true);
            $table->integer('max_backdate_days')->default(0); // 0 = unlimited within valid FY
            $table->boolean('allow_future_dated')->default(false);
            $table->integer('max_future_days')->default(0);
            $table->boolean('allow_voucher_editing')->default(false);
            $table->boolean('allow_voucher_cancellation')->default(true);
            
            // Ledger Controls
            $table->boolean('prevent_inactive_ledger_posting')->default(true);
            
            // Voucher Numbering
            $table->json('voucher_numbering')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
