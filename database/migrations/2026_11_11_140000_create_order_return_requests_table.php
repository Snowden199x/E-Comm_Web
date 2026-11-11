<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per Buyer return request. The Seller approves or rejects it; approval moves the order to
        // `returned`, restores stock and records the refund the Buyer is owed (display only).
        Schema::create('order_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 450)->nullable();
            $table->unsignedInteger('refund_cents')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'status'], 'orr_order_status');
            $table->index(['seller_id', 'status'], 'orr_seller_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_requests');
    }
};
