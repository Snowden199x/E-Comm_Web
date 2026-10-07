<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyer_details', function (Blueprint $table) {
            $table->string('id_type')->default('primary')->after('valid_id_path');
            $table->string('valid_id_path_2')->nullable()->after('id_type');
        });
    }

    public function down(): void
    {
        Schema::table('buyer_details', function (Blueprint $table) {
            $table->dropColumn(['id_type', 'valid_id_path_2']);
        });
    }
};