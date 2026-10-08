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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('account_group_id');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('opening_balance_type', ['Dr', 'Cr'])->default('Dr');
            $table->boolean('is_system')->default(false); // e.g. Cash, Profit & Loss A/c
            $table->string('type')->nullable(); // 'customer', 'vendor', 'bank', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // Customer ID, Vendor ID, etc.
            $table->timestamps();

            $table->foreign('account_group_id')->references('id')->on('account_groups')->onDelete('restrict');
            // Adding a unique constraint for polymorphic relations to avoid duplicate ledgers
            $table->unique(['type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
