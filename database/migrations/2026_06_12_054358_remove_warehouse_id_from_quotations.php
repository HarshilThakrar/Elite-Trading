<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks to allow dropping tables with constraints
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasColumn('quotations', 'warehouse_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                try { $table->dropForeign(['warehouse_id']); } catch (\Exception $e) {}
                $table->dropColumn('warehouse_id');
            });
        }
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
    }
};
