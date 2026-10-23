<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('primary_logistics_coverage', function (Blueprint $table) {
            $table->id();
            $table->string('province_code', 9);
            $table->string('municipality_code', 9);
            $table->foreignId('station_hub_id')->constrained('logistics_facilities')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['province_code', 'municipality_code'], 'plc_location_unique');
            $table->index('station_hub_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('primary_logistics_coverage');
    }
};
