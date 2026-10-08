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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id');
            $table->unsignedBigInteger('ledger_id');
            $table->enum('type', ['Dr', 'Cr']);
            $table->decimal('amount', 15, 2);
            $table->text('narration')->nullable();
            $table->unsignedBigInteger('cost_centre_id')->nullable();
            $table->timestamps();

            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
            $table->foreign('ledger_id')->references('id')->on('ledgers')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
