<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_email_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('new_email')->unique();
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('logistics_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_center_id')->constrained('logistics_centers')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('document_type', 32);
            $table->string('path');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['logistics_center_id', 'status'], 'ldr_center_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_document_requests');
        Schema::dropIfExists('logistics_email_changes');
    }
};
