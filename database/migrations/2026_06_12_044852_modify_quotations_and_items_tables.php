<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('valid_until');
            $table->decimal('freight_charges', 15, 2)->default(0)->after('subtotal');
            $table->decimal('grand_total', 15, 2)->default(0)->after('freight_charges');
            $table->text('terms_and_conditions')->nullable()->after('notes');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('list_price', 15, 2)->default(0)->after('quantity');
            $table->decimal('purchase_discount', 5, 2)->default(0)->after('list_price');
            $table->decimal('customer_discount', 5, 2)->default(0)->after('purchase_discount');
            $table->decimal('purchase_rate', 15, 2)->default(0)->after('customer_discount');
            $table->decimal('customer_rate', 15, 2)->default(0)->after('purchase_rate');
            $table->decimal('profit_amount', 15, 2)->default(0)->after('customer_rate');
            $table->decimal('profit_percentage', 5, 2)->default(0)->after('profit_amount');
            $table->decimal('line_total', 15, 2)->default(0)->after('profit_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'freight_charges', 'grand_total', 'terms_and_conditions']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['list_price', 'purchase_discount', 'customer_discount', 'purchase_rate', 'customer_rate', 'profit_amount', 'profit_percentage', 'line_total']);
        });
    }
};
