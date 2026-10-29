<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row of Vendo Admin rates. Defaults keep today's behavior: Vendo keeps no share of the
        // delivery fee until the Admin sets one, and the seller commission stays at the current 10%.
        Schema::create('delivery_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('vendo_delivery_commission_rate', 5, 2)->default(0);
            $table->decimal('seller_commission_rate', 5, 2)->default(10);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('delivery_fee_settings')->insert([
            'vendo_delivery_commission_rate' => 0,
            'seller_commission_rate' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Fixed pay per delivered parcel, set by each Logistics Company. The default matches the
        // current ₱10 rider credit so nothing changes until a company edits it.
        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->decimal('rider_pay_per_parcel', 10, 2)->default(10)->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Exact cash the Rider must collect, frozen at checkout.
            $table->decimal('cod_amount', 10, 2)->nullable()->after('shipping_route_snapshot');
            // Null means the order was created before cash tracking existed (legacy).
            $table->string('money_status', 30)->nullable()->after('cod_amount');
            // Rates in force when the order was created. Null on legacy orders.
            $table->decimal('rider_pay_rate', 10, 2)->nullable()->after('money_status');
            $table->decimal('vendo_delivery_commission_rate', 5, 2)->nullable()->after('rider_pay_rate');
            $table->decimal('seller_commission_rate', 5, 2)->nullable()->after('vendo_delivery_commission_rate');
            $table->index('money_status', 'orders_money_status_idx');
        });

        Schema::table('order_shipping_settlements', function (Blueprint $table) {
            $table->decimal('vendo_commission', 10, 2)->default(0)->after('rider_total');
        });

        // Backfill the COD amount on existing orders. Buyer payable is the same formula
        // Order::getPayableTotalAttribute uses. Snapshots and money_status stay null for legacy rows.
        DB::table('orders')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                $cents = max(0, (int) round(
                    ((float) $order->total_amount - (float) $order->voucher_discount + (float) $order->shipping_fee) * 100
                ));
                DB::table('orders')->where('id', $order->id)->update([
                    'cod_amount' => number_format($cents / 100, 2, '.', ''),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_shipping_settlements', function (Blueprint $table) {
            $table->dropColumn('vendo_commission');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_money_status_idx');
            $table->dropColumn([
                'cod_amount', 'money_status', 'rider_pay_rate',
                'vendo_delivery_commission_rate', 'seller_commission_rate',
            ]);
        });

        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->dropColumn('rider_pay_per_parcel');
        });

        Schema::dropIfExists('delivery_fee_settings');
    }
};
