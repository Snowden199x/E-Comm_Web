<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A Seller order may now use two vouchers; `voucher_code` keeps the first for older screens. */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('voucher_codes')->nullable()->after('voucher_code');
        });

        DB::table('orders')->whereNotNull('voucher_code')->orderBy('id')->each(function ($order) {
            DB::table('orders')->where('id', $order->id)->update([
                'voucher_codes' => json_encode([$order->voucher_code]),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('voucher_codes');
        });
    }
};
