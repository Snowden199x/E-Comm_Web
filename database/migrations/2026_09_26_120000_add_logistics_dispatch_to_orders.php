<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('logistics_center_id')->nullable()->constrained('logistics_centers')->nullOnDelete();
            $table->foreignId('delivery_courier_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('order_logistics_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->foreignId('from_logistics_center_id')->nullable()->constrained('logistics_centers')->nullOnDelete();
            $table->foreignId('to_logistics_center_id')->nullable()->constrained('logistics_centers')->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logistics_assignments');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_courier_id');
            $table->dropConstrainedForeignId('logistics_center_id');
        });
    }
};
