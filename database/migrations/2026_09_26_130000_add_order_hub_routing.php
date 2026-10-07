<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_province_code', 9)->nullable();
            $table->string('shipping_city_code', 9)->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_city')->nullable();
            $table->foreignId('destination_logistics_center_id')->nullable()->constrained('logistics_centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_logistics_center_id');
            $table->dropColumn(['shipping_province_code', 'shipping_city_code', 'shipping_province', 'shipping_city']);
        });
    }
};
