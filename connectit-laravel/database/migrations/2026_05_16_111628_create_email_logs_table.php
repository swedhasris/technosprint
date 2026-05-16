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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 20); // 'inbound' or 'outbound'
            $table->string('recipient', 255)->nullable();
            $table->string('sender', 255)->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('message_id', 255)->nullable();
            $table->string('status', 50)->default('sent'); // 'pending', 'sent', 'delivered', 'failed', 'received'
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('message_id');
            $table->index('direction');
            $table->index('status');
        });

        // Add thread tracking table for better conversation management
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('thread_id', 255)->unique();
            $table->string('subject', 500)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
        Schema::dropIfExists('email_logs');
    }
};
