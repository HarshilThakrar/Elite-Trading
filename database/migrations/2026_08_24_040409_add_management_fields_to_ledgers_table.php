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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->string('ledger_code')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('type');
            $table->boolean('is_active')->default(true)->after('is_system');
            $table->date('opening_balance_date')->nullable()->after('opening_balance_type');
            $table->json('metadata')->nullable()->after('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn([
                'ledger_code',
                'description',
                'is_active',
                'opening_balance_date',
                'metadata'
            ]);
        });
    }
};
