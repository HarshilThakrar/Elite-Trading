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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->nullable()->unique();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->text('to_emails')->nullable();
            $table->text('cc_emails')->nullable();
            $table->text('bcc_emails')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_plain')->nullable();
            $table->string('folder')->default('inbox'); // inbox, sent, draft, trash
            $table->boolean('is_read')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->unsignedBigInteger('user_id')->nullable(); // if linked to a specific user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
