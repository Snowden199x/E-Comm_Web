<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_details', function (Blueprint $table) {
            $table->foreignId('logistics_center_id')
                ->nullable()
                ->after('user_id')
                ->constrained('logistics_centers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courier_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logistics_center_id');
        });
    }
};