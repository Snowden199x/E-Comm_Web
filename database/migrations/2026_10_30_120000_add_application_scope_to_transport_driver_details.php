<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A Transport Driver now chooses who reviews the application:
 *   station  → the Station Hub approves and the driver works station to station
 *   province → the Province Hub approves and the driver works hub to hub
 *   both     → only the Province Hub approves; the Station Hub only reads
 *
 * station_hub_id is the Station Hub the driver is attached to. It is the chosen
 * Station Hub for a station-only driver, and the Station Hub where the driver
 * lives (or the closest one) for province and both drivers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_driver_details', function (Blueprint $table) {
            $table->string('apply_to', 20)->default('province')->after('province_hub_id');
            $table->foreignId('station_hub_id')->nullable()->after('apply_to')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            // Vehicle details the driver declares on the application.
            $table->string('vehicle_type', 40)->nullable()->after('home_address');
            $table->string('plate_number', 20)->nullable()->after('vehicle_type');
        });
    }

    public function down(): void
    {
        Schema::table('transport_driver_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_hub_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['apply_to', 'approved_at', 'vehicle_type', 'plate_number']);
        });
    }
};
