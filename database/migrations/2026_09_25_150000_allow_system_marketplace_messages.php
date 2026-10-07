<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_messages', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('marketplace_messages')->whereNull('sender_id')->exists()) {
            throw new RuntimeException('Remove system messages before rolling back this migration.');
        }

        Schema::table('marketplace_messages', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable(false)->change();
        });
    }
};
