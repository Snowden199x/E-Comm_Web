<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per delivered order: the cash the Rider actually collected.
        Schema::create('order_cod_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('station_hub_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->uuid('scan_key')->unique();
            $table->timestamp('collected_at');
            $table->timestamps();
            $table->index(['station_hub_id', 'collected_at'], 'occ_station_collected');
            $table->index(['rider_user_id', 'collected_at'], 'occ_rider_collected');
        });

        // One row per cash handoff. level is one of:
        // rider_to_station, station_to_province, province_to_company, company_to_vendo.
        Schema::create('cash_remittances', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('level', 30);
            $table->foreignId('from_rider_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_facility_id')->nullable()->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('to_facility_id')->nullable()->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('logistics_company_id')->nullable()->constrained('logistics_companies')->nullOnDelete();
            $table->decimal('expected_amount', 12, 2);
            $table->decimal('received_amount', 12, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['level', 'status'], 'cr_level_status');
            $table->index(['to_facility_id', 'status'], 'cr_to_status');
            $table->index(['from_facility_id', 'status'], 'cr_from_status');
            $table->index(['logistics_company_id', 'level', 'status'], 'cr_company_level_status');
        });

        // The orders inside a remittance. An order can be included at most once per level, so a
        // retried or double-submitted batch cannot count the same cash twice.
        Schema::create('cash_remittance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remittance_id')->constrained('cash_remittances')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('level', 30);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->unique(['remittance_id', 'order_id'], 'cri_remittance_order_unique');
            $table->unique(['order_id', 'level'], 'cri_order_level_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_remittance_items');
        Schema::dropIfExists('cash_remittances');
        Schema::dropIfExists('order_cod_collections');
    }
};
