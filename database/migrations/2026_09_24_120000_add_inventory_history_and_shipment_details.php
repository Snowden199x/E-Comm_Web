<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->integer('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 500)->nullable();
            $table->uuid('request_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number', 50)->nullable()->unique();
            $table->string('carrier_name', 100)->nullable();
            $table->string('carrier_tracking_number', 100)->nullable()->index();
            $table->date('estimated_delivery_from')->nullable();
            $table->date('estimated_delivery_to')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);
        });
        DB::table('orders')->select('id')->orderBy('id')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'tracking_number' => 'VND-'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_number']);
            $table->dropIndex(['carrier_tracking_number']);
            $table->dropColumn(['tracking_number', 'carrier_name', 'carrier_tracking_number', 'estimated_delivery_from', 'estimated_delivery_to', 'shipping_fee']);
        });
        Schema::dropIfExists('inventory_movements');
    }
};
