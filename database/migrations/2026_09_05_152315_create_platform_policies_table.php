<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('platform_policies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('version')->default('1.0');
        $table->longText('content')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('platform_policies');
}
};
