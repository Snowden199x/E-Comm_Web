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
    Schema::create('complaint_evidences', function (Blueprint $table) {
        $table->id();
        $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
        $table->string('path');
        $table->string('original_filename')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('complaint_evidences');
}
};
