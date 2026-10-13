<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_vouchers', function (Blueprint $table) {
            $table->timestamp('new_voucher_notified_at')->nullable();
            $table->timestamp('expired_notified_at')->nullable();
        });
        DB::table('seller_vouchers')->update(['new_voucher_notified_at' => now()]);
        DB::table('seller_vouchers')->where('status', 'active')->where(function ($query) {
            $query->where(fn ($q) => $q->whereNotNull('ends_at')->where('ends_at', '<=', now()))
                ->orWhere(fn ($q) => $q->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit'));
        })->update(['expired_notified_at' => now()]);

        Schema::create('seller_voucher_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('seller_vouchers')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->timestamps();
            $table->unique(['voucher_id', 'buyer_id'], 'voucher_notice_buyer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_voucher_notification_recipients');
        Schema::table('seller_vouchers', function (Blueprint $table) {
            $table->dropColumn(['new_voucher_notified_at', 'expired_notified_at']);
        });
    }
};
