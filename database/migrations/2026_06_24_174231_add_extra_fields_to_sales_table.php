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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('delivery_note')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->string('reference_no_date')->nullable();
            $table->string('other_references')->nullable();
            $table->string('buyer_order_no')->nullable();
            $table->date('buyer_order_date')->nullable();
            $table->string('dispatched_through')->nullable();
            $table->string('destination')->nullable();
            $table->text('terms_of_delivery')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_note',
                'mode_of_payment',
                'reference_no_date',
                'other_references',
                'buyer_order_no',
                'buyer_order_date',
                'dispatched_through',
                'destination',
                'terms_of_delivery'
            ]);
        });
    }
};
