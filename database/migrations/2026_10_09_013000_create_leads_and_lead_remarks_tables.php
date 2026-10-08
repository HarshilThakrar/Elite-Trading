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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_code')->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('source')->default('Direct / Walk-in'); // Website, Referral, Cold Call, Social Media, Exhibition, etc.
            $table->string('status')->default('New'); // New, Contacted, Qualified, Proposal Sent, Negotiation, Won, Lost
            $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_close_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->text('requirement_details')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remark');
            $table->string('action_type')->default('remark'); // created, updated, status_changed, remark
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->boolean('is_notification')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_remarks');
        Schema::dropIfExists('leads');
    }
};
