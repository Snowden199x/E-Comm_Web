<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Admin override: a later return deadline than delivered_at + 7 days. Null keeps the default.
            $table->timestamp('return_window_until')->nullable()->after('delivery_attempts');
            // Admin override: extra delivery attempts granted beyond DeliveryCutoff::MAX_DELIVERY_ATTEMPTS.
            $table->unsignedTinyInteger('delivery_extra_attempts')->default(0)->after('return_window_until');
            // Admin override: who granted the last limit override and why (audit line for the timeline).
            $table->foreignId('limit_override_by')->nullable()->after('delivery_extra_attempts')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('limit_override_at')->nullable()->after('limit_override_by');
            $table->string('limit_override_note', 450)->nullable()->after('limit_override_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('limit_override_by');
            $table->dropColumn(['return_window_until', 'delivery_extra_attempts', 'limit_override_at', 'limit_override_note']);
        });
    }
};
