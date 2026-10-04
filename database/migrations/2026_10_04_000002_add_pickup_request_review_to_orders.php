<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_request_status', 16)->default('pending');
            $table->text('pickup_decline_reason')->nullable();
            $table->timestamp('pickup_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn([
            'pickup_request_status', 'pickup_decline_reason', 'pickup_verified_at',
        ]));
    }
};
