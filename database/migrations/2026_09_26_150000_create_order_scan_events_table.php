<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('logistics_center_id')->nullable()->constrained('logistics_centers')->nullOnDelete();
            $table->string('scan_type', 40);
            $table->string('from_status', 40);
            $table->string('to_status', 40);
            $table->uuid('scan_key');
            $table->timestamps();
            $table->unique(['order_id', 'scan_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_scan_events');
    }
};
