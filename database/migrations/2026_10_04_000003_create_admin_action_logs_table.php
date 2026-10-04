<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['target_user_id', 'created_at'], 'admin_action_target_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
