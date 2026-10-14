<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_facility_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('sorted_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'position'], 'ofs_order_position_unique');
            $table->unique(['order_id', 'facility_id'], 'ofs_order_facility_unique');
        });

        Schema::create('facility_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 32);
            $table->uuid('scan_key')->unique();
            $table->timestamps();
            $table->unique(['order_id', 'facility_id', 'action'], 'fse_one_action_per_stop');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_scan_events');
        Schema::dropIfExists('order_facility_stops');
    }
};
