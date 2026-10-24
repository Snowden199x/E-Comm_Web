<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_trips', function (Blueprint $table) {
            $table->timestamp('expected_arrival_at')->nullable()->after('scheduled_departure_at');
            $table->index(['status', 'expected_arrival_at'], 'tt_status_expected_arrival');
        });

        Schema::create('cross_company_manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_company_id')->constrained('logistics_companies')->restrictOnDelete();
            $table->foreignId('destination_company_id')->constrained('logistics_companies')->restrictOnDelete();
            $table->foreignId('origin_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('destination_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('transport_driver_details')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('company_vehicles')->nullOnDelete();
            $table->string('status', 20)->default('in_transit');
            $table->timestamp('expected_arrival_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['origin_company_id', 'status'], 'ccm_origin_status');
            $table->index(['destination_company_id', 'status'], 'ccm_destination_status');
        });

        Schema::table('manual_logistics_handoffs', function (Blueprint $table) {
            $table->foreignId('manifest_id')->nullable()->after('order_id')
                ->constrained('cross_company_manifests')->nullOnDelete();
        });

        Schema::create('logistics_parcel_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 40);
            $table->text('details');
            $table->string('status', 20)->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status'], 'lpe_order_status');
            $table->index(['facility_id', 'status'], 'lpe_facility_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_parcel_exceptions');
        Schema::table('manual_logistics_handoffs', fn (Blueprint $table) => $table->dropConstrainedForeignId('manifest_id'));
        Schema::dropIfExists('cross_company_manifests');
        Schema::table('transport_trips', function (Blueprint $table) {
            $table->dropIndex('tt_status_expected_arrival');
            $table->dropColumn('expected_arrival_at');
        });
    }
};
