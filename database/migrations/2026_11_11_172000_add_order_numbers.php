<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seller-visible order numbers become ORD-YYYY-0001, generated from a yearly counter. Existing
     * orders are numbered by their creation year in id order, so their new numbers are stable. The
     * `number` accessor still falls back to the legacy VN- id if a row has no stored number.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 24)->nullable()->unique()->after('id');
        });

        Schema::create('order_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        $counters = [];
        DB::table('orders')->orderBy('id')->select(['id', 'created_at'])->chunkById(500, function ($orders) use (&$counters) {
            foreach ($orders as $order) {
                $year = $order->created_at ? Carbon::parse($order->created_at)->year : (int) date('Y');
                $counters[$year] = ($counters[$year] ?? 0) + 1;
                DB::table('orders')->where('id', $order->id)->update([
                    'order_number' => sprintf('ORD-%d-%04d', $year, $counters[$year]),
                ]);
            }
        });

        foreach ($counters as $year => $last) {
            DB::table('order_sequences')->insert([
                'year' => $year,
                'last_number' => $last,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sequences');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }
};
