<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The day the next delivery attempt is scheduled for. Null means the
            // parcel is still on today's run.
            $table->date('delivery_scheduled_for')->nullable()->after('delivered_at');
            // When the 5:00 PM window closed without a delivery. Kept as history
            // even after the Rider reschedules the parcel.
            $table->timestamp('delivery_failed_at')->nullable()->after('delivery_scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_scheduled_for', 'delivery_failed_at']);
        });
    }
};
