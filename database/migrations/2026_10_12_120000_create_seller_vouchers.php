<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('code', 12);
            $table->string('type', 10);
            $table->decimal('value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->decimal('min_spend', 10, 2)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_buyer_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('scope', 12)->default('all');
            $table->string('status', 10)->default('active');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->unique(['seller_id', 'code']);
            $table->index(['seller_id', 'status']);
        });
        Schema::create('seller_voucher_products', function (Blueprint $table) {
            $table->foreignId('voucher_id')->constrained('seller_vouchers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['voucher_id', 'product_id']);
        });
        Schema::create('seller_voucher_categories', function (Blueprint $table) {
            $table->foreignId('voucher_id')->constrained('seller_vouchers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['voucher_id', 'category_id']);
        });
        Schema::create('seller_voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('seller_vouchers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('discount', 10, 2);
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->unique('order_id');
            $table->index(['voucher_id', 'buyer_id', 'voided_at'], 'voucher_buyer_void_idx');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('voucher_discount', 10, 2)->default(0);
            $table->string('voucher_code', 12)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['voucher_discount', 'voucher_code']));
        Schema::dropIfExists('seller_voucher_redemptions');
        Schema::dropIfExists('seller_voucher_categories');
        Schema::dropIfExists('seller_voucher_products');
        Schema::dropIfExists('seller_vouchers');
    }
};
