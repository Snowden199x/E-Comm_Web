<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seller pickup scheduling: the agreed pickup date/time, how many times the
     * seller moved it (three moves allowed), how many assignments expired without
     * a rider scan, and when the seller last reported a pickup problem.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('pickup_scheduled_at')->nullable()->after('pickup_verified_at');
            $table->unsignedTinyInteger('pickup_reschedule_count')->default(0)->after('pickup_scheduled_at');
            $table->unsignedTinyInteger('pickup_attempts')->default(0)->after('pickup_reschedule_count');
            $table->timestamp('pickup_reported_at')->nullable()->after('pickup_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_scheduled_at', 'pickup_reschedule_count', 'pickup_attempts', 'pickup_reported_at']);
        });
    }
};
