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
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign keys if they exist (we can assume standard naming)
            // Using exception handling in case sqlite doesn't support dropping foreign keys easily, 
            // but we can try dropping the columns.
            try { $table->dropForeign(['brand_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['category_id']); } catch (\Exception $e) {}
            $table->dropColumn(['brand_id', 'category_id']);
        });

        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Reverting this is complex, we just leave it empty or throw exception
    }
};
