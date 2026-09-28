<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('linehaul_rider_id')->nullable()->after('courier_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['linehaul_rider_id', 'status'], 'orders_linehaul_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_linehaul_status_idx');
            $table->dropConstrainedForeignId('linehaul_rider_id');
        });
    }
};
