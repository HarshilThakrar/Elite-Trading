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
            $table->dropColumn(['group', 'subgroup']);
            $table->foreignId('product_group_id')->nullable()->after('part_code')->constrained()->nullOnDelete();
            $table->foreignId('product_subgroup_id')->nullable()->after('product_group_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_group_id']);
            $table->dropForeign(['product_subgroup_id']);
            $table->dropColumn(['product_group_id', 'product_subgroup_id']);
            $table->string('group')->nullable()->after('part_code');
            $table->string('subgroup')->nullable()->after('group');
        });
    }
};
