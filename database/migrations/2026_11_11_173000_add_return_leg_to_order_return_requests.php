<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * After a Seller approves a return, the parcel still has to travel back: the Rider collects it
     * from the Buyer and hands it over to the Seller. These columns follow that trip. Stock is
     * restored at approval; the trip is physical evidence, not a second stock movement.
     */
    public function up(): void
    {
        Schema::table('order_return_requests', function (Blueprint $table) {
            $table->string('return_status', 30)->default('not_started')->after('status');
            $table->foreignId('pickup_rider_id')->nullable()->after('return_status')->constrained('users')->nullOnDelete();
            $table->timestamp('picked_up_at')->nullable()->after('pickup_rider_id');
            $table->timestamp('delivered_to_seller_at')->nullable()->after('picked_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_return_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_rider_id');
            $table->dropColumn(['return_status', 'picked_up_at', 'delivered_to_seller_at']);
        });
    }
};
