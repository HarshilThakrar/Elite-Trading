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
        // 1. Add available_stock to products
        if (!Schema::hasColumn('products', 'available_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('available_stock')->default(0)->after('status');
            });
        }

        // Disable foreign key checks to allow dropping tables with constraints
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Drop warehouse_id from purchases and sales
        if (Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                try { $table->dropForeign(['warehouse_id']); } catch (\Exception $e) {}
                $table->dropColumn('warehouse_id');
            });
        }
        
        if (Schema::hasColumn('sales', 'warehouse_id')) {
            Schema::table('sales', function (Blueprint $table) {
                try { $table->dropForeign(['warehouse_id']); } catch (\Exception $e) {}
                $table->dropColumn('warehouse_id');
            });
        }

        // 3. Drop all related inventory tables
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('adjustment_items');
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('warehouses');

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // Reverting this is complex, leaving empty
    }
};
