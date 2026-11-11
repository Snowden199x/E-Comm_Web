<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Closed delivery attempts. The first failure sends the parcel back to the destination
            // Station Hub for one final attempt; the second cancels the order automatically.
            $table->unsignedTinyInteger('delivery_attempts')->default(0)->after('delivery_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_attempts');
        });
    }
};
