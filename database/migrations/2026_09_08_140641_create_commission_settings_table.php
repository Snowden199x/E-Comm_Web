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
    Schema::create('commission_settings', function (Blueprint $table) {
        $table->id();
        $table->decimal('rate', 5, 2)->default(10.00);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('commission_settings');
}
};
