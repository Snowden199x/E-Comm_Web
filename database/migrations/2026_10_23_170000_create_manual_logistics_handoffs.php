<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_logistics_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('origin_company_id')->constrained('logistics_companies')->restrictOnDelete();
            $table->foreignId('destination_company_id')->constrained('logistics_companies')->restrictOnDelete();
            $table->foreignId('origin_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('destination_facility_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->string('status', 24)->default('awaiting_origin');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['origin_company_id', 'status'], 'mlh_origin_status');
            $table->index(['destination_company_id', 'status'], 'mlh_destination_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_logistics_handoffs');
    }
};
