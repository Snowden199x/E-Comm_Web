<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'last_message_at']);
            $table->index(['buyer_id', 'last_message_at']);
        });

        Schema::create('marketplace_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['marketplace_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_messages');
        Schema::dropIfExists('marketplace_conversations');
    }
};
