<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_details', function (Blueprint $table) {
            $table->string('shop_banner_path')->nullable();
            $table->text('shop_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seller_details', function (Blueprint $table) {
            $table->dropColumn(['shop_banner_path', 'shop_description']);
        });
    }
};
