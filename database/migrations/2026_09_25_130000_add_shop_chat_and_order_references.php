<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_conversations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
        });

        Schema::table('marketplace_messages', function (Blueprint $table) {
            $table->foreignId('shared_order_id')->nullable()->after('order_item_id')
                ->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('marketplace_conversations')->whereNull('order_id')->exists()) {
            throw new RuntimeException('Shop conversations must be archived before rolling back this migration.');
        }

        Schema::table('marketplace_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shared_order_id');
        });

        Schema::table('marketplace_conversations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
