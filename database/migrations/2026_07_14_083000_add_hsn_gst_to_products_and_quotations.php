<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add HSN code and GST rate to products
        Schema::table('products', function (Blueprint $table) {
            $table->string('hsn_code', 20)->nullable()->after('part_code');
            $table->decimal('gst_rate', 5, 2)->default(18.00)->after('hsn_code'); // GST rate in %
        });

        // Add GST amount to quotation_items
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('line_total');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('gst_rate');
            $table->decimal('line_total_with_gst', 12, 2)->default(0)->after('gst_amount');
        });

        // Add GST total to quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('gst_amount', 12, 2)->default(0)->after('grand_total');
            $table->decimal('grand_total_with_gst', 12, 2)->default(0)->after('gst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'gst_rate']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['gst_rate', 'gst_amount', 'line_total_with_gst']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['gst_amount', 'grand_total_with_gst']);
        });
    }
};
