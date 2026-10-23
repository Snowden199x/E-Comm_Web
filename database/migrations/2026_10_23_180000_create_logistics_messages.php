<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('logistics_facilities')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('subject', 160);
            $table->string('topic', 40);
            $table->string('priority', 12)->default('normal');
            $table->string('status', 12)->default('open');
            $table->timestamps();
            $table->index(['logistics_company_id', 'updated_at'], 'lt_company_updated');
            $table->index(['facility_id', 'updated_at'], 'lt_facility_updated');
        });

        Schema::create('logistics_thread_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_thread_id')->constrained('logistics_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['logistics_thread_id', 'created_at'], 'ltm_thread_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_thread_messages');
        Schema::dropIfExists('logistics_threads');
    }
};
