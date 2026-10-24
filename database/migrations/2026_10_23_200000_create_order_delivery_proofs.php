<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('station_hub_id')->nullable()->constrained('logistics_facilities')->nullOnDelete();
            $table->uuid('scan_key')->unique();
            $table->string('recipient_name', 120);
            $table->string('photo_path');
            $table->text('notes')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_delivery_proofs');
    }
};
