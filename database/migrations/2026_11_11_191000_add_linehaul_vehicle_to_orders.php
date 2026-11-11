<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Linehaul now moves on a company truck (plate number) instead of a
     * "Truck Rider" vehicle type. The driver stays in linehaul_rider_id.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('linehaul_vehicle_id')->nullable()->after('linehaul_rider_id')
                ->constrained('company_vehicles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linehaul_vehicle_id');
        });
    }
};
