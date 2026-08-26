<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Personal Information
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('birthday');
            $table->string('valid_id_path')->nullable();

            // User Address
            $table->string('province');
            $table->string('municipality');
            $table->string('barangay');
            $table->string('street')->nullable();
            $table->string('house_no')->nullable();
            $table->string('zip_code')->nullable();

            // Vehicle Information
            $table->string('vehicle_type');
            $table->string('plate_number');
            $table->string('drivers_license_path');
            $table->string('or_cr_path');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_details');
    }
};