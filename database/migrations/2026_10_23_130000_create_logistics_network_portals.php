<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_center_id')->unique()->constrained('logistics_centers')->cascadeOnDelete();
            $table->string('business_name');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::table('logistics_facilities', function (Blueprint $table) {
            $table->foreignId('logistics_company_id')->nullable()->after('logistics_center_id')
                ->constrained('logistics_companies')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('logistics_company_id')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->string('province_hub_key', 16)->nullable();
            $table->string('station_municipality_key', 16)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->index(['logistics_company_id', 'kind', 'is_active'], 'lf_company_kind_active');
            $table->index(['parent_id', 'kind', 'is_active'], 'lf_parent_kind_active');
        });

        $centers = DB::table('logistics_centers')->orderBy('id')->get();
        foreach ($centers as $center) {
            $companyId = DB::table('logistics_companies')->insertGetId([
                'logistics_center_id' => $center->id,
                'business_name' => $center->business_name,
                'status' => DB::table('users')->where('id', $center->user_id)->value('status') ?? 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('logistics_facilities')->where('logistics_center_id', $center->id)
                ->update(['logistics_company_id' => $companyId]);

            $mainHub = DB::table('logistics_facilities')->where('logistics_center_id', $center->id)
                ->where('kind', 'main_hub')->first();
            if ($mainHub) {
                DB::table('logistics_facilities')->where('id', $mainHub->id)->update([
                    'kind' => 'province_hub', 'province_hub_key' => $mainHub->province_code,
                ]);
                DB::table('logistics_facilities')->where('logistics_center_id', $center->id)->where('kind', 'station')->update([
                    'kind' => 'station_hub', 'parent_id' => $mainHub->id,
                    'station_municipality_key' => DB::raw('municipality_code'),
                ]);
            }
        }

        Schema::table('logistics_centers', function (Blueprint $table) {
            $table->dropUnique('lc_coverage_province_unique');
        });

        Schema::create('facility_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('logistics_facilities')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('staff_title', 40)->default('manager');
            $table->timestamps();
            $table->unique('facility_id', 'facility_staff_one_manager');
            $table->index(['facility_id', 'staff_title']);
        });

        Schema::table('courier_details', function (Blueprint $table) {
            $table->foreignId('station_hub_id')->nullable()->after('logistics_center_id')
                ->constrained('logistics_facilities')->nullOnDelete();
        });

        Schema::create('rider_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_detail_id')->constrained('courier_details')->cascadeOnDelete();
            $table->foreignId('station_hub_id')->constrained('logistics_facilities')->cascadeOnDelete();
            $table->string('barangay_code', 12);
            $table->string('barangay');
            $table->timestamps();
            $table->unique(['courier_detail_id', 'barangay_code'], 'rsa_rider_barangay_unique');
            $table->index(['station_hub_id', 'barangay_code'], 'rsa_station_barangay');
        });

        Schema::create('rider_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('courier_details')->cascadeOnDelete();
            $table->foreignId('station_hub_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('status', 20)->default('assigned');
            $table->unsignedSmallInteger('sequence')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['station_hub_id', 'status', 'type'], 'rt_station_status_type');
            $table->index(['rider_id', 'status', 'sequence'], 'rt_rider_status_sequence');
            $table->index(['order_id', 'type', 'status'], 'rt_order_type_status');
        });

        Schema::create('transport_driver_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('province_hub_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->string('license_number', 80);
            $table->string('license_category', 40);
            $table->date('license_expires_at');
            $table->string('drivers_license_path');
            $table->string('valid_id_path');
            $table->string('status', 20)->default('available');
            $table->timestamps();
            $table->index(['province_hub_id', 'status'], 'tdd_hub_status');
        });

        Schema::create('company_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->foreignId('province_hub_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->string('vehicle_code', 40);
            $table->string('plate_number', 24);
            $table->string('vehicle_type', 40);
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->string('status', 20)->default('available');
            $table->text('maintenance_note')->nullable();
            $table->timestamp('maintenance_at')->nullable();
            $table->timestamps();
            $table->unique(['logistics_company_id', 'vehicle_code'], 'cv_company_code_unique');
            $table->unique(['logistics_company_id', 'plate_number'], 'cv_company_plate_unique');
            $table->index(['province_hub_id', 'status'], 'cv_hub_status');
        });

        Schema::create('transport_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->string('trip_type', 24);
            $table->foreignId('origin_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('destination_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('transport_driver_details')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('company_vehicles')->restrictOnDelete();
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('scheduled_departure_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['logistics_company_id', 'status', 'scheduled_departure_at'], 'tt_company_status_time');
            $table->index(['origin_facility_id', 'destination_facility_id'], 'tt_route_facilities');
        });

        Schema::create('transport_trip_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('transport_trips')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['trip_id', 'order_id'], 'tto_trip_order_unique');
            $table->index('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('origin_station_id')->nullable()->after('logistics_center_id')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('origin_province_hub_id')->nullable()->after('origin_station_id')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('destination_station_id')->nullable()->after('destination_logistics_center_id')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('destination_province_hub_id')->nullable()->after('destination_station_id')
                ->constrained('logistics_facilities')->nullOnDelete();
            $table->string('route_type', 24)->nullable();
            $table->string('shipping_barangay_code', 12)->nullable();
            $table->string('shipping_barangay')->nullable();
            $table->index(['origin_station_id', 'status'], 'orders_origin_station_status');
            $table->index(['destination_station_id', 'status'], 'orders_destination_station_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_origin_station_status');
            $table->dropIndex('orders_destination_station_status');
            $table->dropForeign(['origin_station_id']);
            $table->dropForeign(['origin_province_hub_id']);
            $table->dropForeign(['destination_station_id']);
            $table->dropForeign(['destination_province_hub_id']);
            $table->dropColumn([
                'origin_station_id', 'origin_province_hub_id', 'destination_station_id',
                'destination_province_hub_id', 'route_type', 'shipping_barangay_code', 'shipping_barangay',
            ]);
        });
        Schema::dropIfExists('transport_trip_orders');
        Schema::dropIfExists('transport_trips');
        Schema::dropIfExists('company_vehicles');
        Schema::dropIfExists('transport_driver_details');
        Schema::dropIfExists('rider_tasks');
        Schema::dropIfExists('rider_service_areas');
        Schema::table('courier_details', fn (Blueprint $table) => $table->dropConstrainedForeignId('station_hub_id'));
        Schema::dropIfExists('facility_staff');
        Schema::table('logistics_facilities', function (Blueprint $table) {
            $table->dropIndex('lf_company_kind_active');
            $table->dropIndex('lf_parent_kind_active');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('logistics_company_id');
            $table->dropColumn(['province_hub_key', 'station_municipality_key', 'latitude', 'longitude']);
        });
        Schema::dropIfExists('logistics_companies');
    }
};
