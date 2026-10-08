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
        Schema::create('purchase_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_plan_id')->constrained('purchase_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('required_quantity', 15, 2)->default(0);
            $table->foreignId('suggested_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_plan_items');
    }
};
