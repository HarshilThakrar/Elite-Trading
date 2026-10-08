<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\AccountGroup;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('account_groups', 'normal_balance')) {
                $table->enum('normal_balance', ['Dr', 'Cr'])->nullable()->after('nature');
            }
            if (!Schema::hasColumn('account_groups', 'description')) {
                $table->text('description')->nullable()->after('normal_balance');
            }
            if (!Schema::hasColumn('account_groups', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system');
            }
            if (!Schema::hasColumn('account_groups', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Set normal_balance for existing groups
        // Assets -> Dr, Expenses -> Dr, Liabilities -> Cr, Income -> Cr, Capital -> Cr
        DB::table('account_groups')->where('nature', 'Assets')->update(['normal_balance' => 'Dr']);
        DB::table('account_groups')->where('nature', 'Expenses')->update(['normal_balance' => 'Dr']);
        DB::table('account_groups')->where('nature', 'Liabilities')->update(['normal_balance' => 'Cr']);
        DB::table('account_groups')->where('nature', 'Income')->update(['normal_balance' => 'Cr']);

        // Insert Capital root group if not exists
        if (!DB::table('account_groups')->where('name', 'Capital Account')->exists()) {
            DB::table('account_groups')->insert([
                'name' => 'Capital Account',
                'parent_id' => null,
                'nature' => 'Capital',
                'normal_balance' => 'Cr',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            $table->dropColumn(['normal_balance', 'description', 'is_active', 'sort_order']);
        });
        
        DB::table('account_groups')->where('name', 'Capital Account')->where('is_system', true)->delete();
    }
};
