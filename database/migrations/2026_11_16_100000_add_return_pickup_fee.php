<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fixed fee the Admin sets for a return pickup. It is separate from the Company's rider pay
        // per delivered parcel and defaults to today's ₱10 planning value.
        Schema::table('delivery_fee_settings', function (Blueprint $table) {
            $table->decimal('return_pickup_fee', 10, 2)->default(10)->after('seller_commission_rate');
        });
        DB::table('delivery_fee_settings')->update(['return_pickup_fee' => 10]);

        Schema::table('order_return_requests', function (Blueprint $table) {
            // Frozen when the Seller approves, so a later settings change cannot move an approved return.
            $table->unsignedInteger('pickup_fee_cents')->default(0)->after('refund_cents');
            // seller for damaged/wrong/missing/not-as-described; buyer for other.
            $table->string('pickup_fee_payer', 10)->nullable()->after('pickup_fee_cents');
        });

        // One row per collected return. It is written at the pickup scan, never at handover, and the
        // unique return keeps a retried scan from paying twice.
        Schema::create('order_return_pickup_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->unique()->constrained('order_return_requests')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('logistics_facilities')->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payer', 10);
            $table->timestamps();
            $table->index(['rider_user_id', 'created_at'], 'orpickup_rider_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_pickup_earnings');

        Schema::table('order_return_requests', function (Blueprint $table) {
            $table->dropColumn(['pickup_fee_cents', 'pickup_fee_payer']);
        });

        Schema::table('delivery_fee_settings', function (Blueprint $table) {
            $table->dropColumn('return_pickup_fee');
        });
    }
};
