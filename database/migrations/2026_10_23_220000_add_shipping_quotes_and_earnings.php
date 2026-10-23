<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('shipping_hub_count')->nullable()->after('shipping_fee');
            $table->json('shipping_route_snapshot')->nullable()->after('shipping_hub_count');
        });

        Schema::create('order_shipping_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->decimal('shipping_fee', 10, 2);
            $table->unsignedSmallInteger('hub_count');
            $table->decimal('rider_total', 10, 2)->default(0);
            $table->decimal('logistics_remainder', 10, 2);
            $table->string('status', 20)->default('accrued');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'settled_at'], 'oss_status_settled');
        });

        Schema::create('order_shipping_earning_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('order_shipping_settlements')->cascadeOnDelete();
            $table->unsignedSmallInteger('route_position');
            $table->foreignId('facility_id')->nullable()->constrained('logistics_facilities')->nullOnDelete();
            $table->foreignId('rider_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->unique(['settlement_id', 'route_position'], 'osel_settlement_position_unique');
            $table->index(['rider_user_id', 'created_at'], 'osel_rider_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipping_earning_lines');
        Schema::dropIfExists('order_shipping_settlements');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_hub_count', 'shipping_route_snapshot']);
        });
    }
};
