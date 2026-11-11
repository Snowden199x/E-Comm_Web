<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buyer support tickets. A ticket is a conversation with Vendo Admin, separate from a complaint
     * about a seller or an order. Messages keep whether the sender was Vendo staff so the Buyer view
     * can align the bubbles without extra joins.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('category', 30)->default('other');
            $table->string('subject', 140);
            $table->string('status', 20)->default('open');
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'support_tickets_user_status');
            $table->index(['status', 'last_reply_at'], 'support_tickets_status_reply');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at'], 'support_messages_ticket_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
