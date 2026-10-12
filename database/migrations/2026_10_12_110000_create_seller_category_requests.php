<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_category_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('permit_path');
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'status']);
        });

        Schema::create('seller_category_request_categories', function (Blueprint $table) {
            $table->foreignId('request_id')->constrained('seller_category_requests')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['request_id', 'category_id'], 'seller_cat_req_category_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_category_request_categories');
        Schema::dropIfExists('seller_category_requests');
    }
};
