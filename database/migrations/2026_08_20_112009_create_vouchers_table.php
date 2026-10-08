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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->date('date');
            $table->string('type'); // 'Sales', 'Purchase', 'Receipt', 'Payment', 'Contra', 'Journal', 'Debit Note', 'Credit Note'
            $table->text('narration')->nullable();
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('reference_id')->nullable(); // Order ID, Invoice ID etc.
            $table->string('reference_type')->nullable(); // Morph to original document
            $table->unsignedBigInteger('created_by')->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Posted', 'Cancelled'])->default('Draft');
            $table->timestamps();

            $table->foreign('financial_year_id')->references('id')->on('financial_years')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
