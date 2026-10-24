<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_driver_details', function (Blueprint $table) {
            $table->date('birthday')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('home_address', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transport_driver_details', fn (Blueprint $table) =>
            $table->dropColumn(['birthday', 'sex', 'home_address']));
    }
};
