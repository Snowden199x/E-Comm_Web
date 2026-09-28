<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_sub_hubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_center_id')->constrained('logistics_centers')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('province');
            $table->string('municipality');
            $table->string('province_code', 9)->nullable();
            $table->string('municipality_code', 9)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['province', 'municipality', 'is_active']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('next_sub_hub_id')->nullable()
                ->after('destination_logistics_center_id')
                ->constrained('logistics_sub_hubs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_sub_hub_id');
        });

        Schema::dropIfExists('logistics_sub_hubs');
    }
};
