<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('users');
            $table->foreignId('courier_id')->nullable()->constrained('users');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', [
                'pending',
                'to_ship',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'cancelled',
                'returned',
            ])->default('pending');
            $table->string('payment_mode')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};