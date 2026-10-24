<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['for_review', 'approved', 'rejected', 'warned'])
                ->default('for_review')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['for_review', 'approved', 'rejected'])
                ->default('for_review')->change();
        });
    }
};
