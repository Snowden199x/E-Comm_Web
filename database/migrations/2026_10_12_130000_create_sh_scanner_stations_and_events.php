<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sh_scanner_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained('logistics_route_checkpoints')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('pairing_code_hash', 64)->nullable()->unique();
            $table->timestamp('pairing_expires_at')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['logistics_center_id', 'checkpoint_id'], 'sh_station_center_checkpoint_idx');
        });

        Schema::create('sh_checkpoint_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('sh_scanner_stations')->restrictOnDelete();
            $table->foreignId('checkpoint_id')->constrained('logistics_route_checkpoints')->restrictOnDelete();
            $table->foreignId('route_plan_id')->constrained('logistics_route_plans')->restrictOnDelete();
            $table->unsignedSmallInteger('route_step');
            $table->uuid('scan_key')->unique();
            $table->timestamps();

            $table->unique(['order_id', 'route_plan_id', 'route_step'], 'sh_scan_order_plan_step_unique');
            $table->index(['station_id', 'created_at'], 'sh_scan_station_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sh_checkpoint_scan_events');
        Schema::dropIfExists('sh_scanner_stations');
    }
};
