<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE products MODIFY status ENUM('draft', 'for_review', 'approved', 'rejected', 'warned') NOT NULL DEFAULT 'for_review'");
        }

        Schema::create('product_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('condition', 12)->nullable();
            $table->string('video_path')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('package_length', 8, 1)->nullable();
            $table->decimal('package_width', 8, 1)->nullable();
            $table->decimal('package_height', 8, 1)->nullable();
            $table->boolean('is_fragile')->default(false);
            $table->boolean('has_variations')->default(false);
            $table->decimal('compare_at_price', 10, 2)->nullable();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->json('value');
            $table->unique(['product_id', 'key'], 'product_attr_product_key_unique');
        });
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('value', 255);
            $table->unsignedSmallInteger('sort_order');
        });
        Schema::create('product_variation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->unsignedTinyInteger('sort_order');
        });
        Schema::create('product_variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_type_id')->constrained()->cascadeOnDelete();
            $table->string('value', 30);
            $table->unsignedTinyInteger('sort_order');
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label', 255);
            $table->string('sku', 80)->unique();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->json('options');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::table('cart_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::table('inventory_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_variation_options');
        Schema::dropIfExists('product_variation_types');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_attribute_values');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['condition', 'video_path', 'weight_kg', 'package_length', 'package_width',
                'package_height', 'is_fragile', 'has_variations', 'compare_at_price']);
        });
        Schema::dropIfExists('product_sequences');
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE products MODIFY status ENUM('for_review', 'approved', 'rejected', 'warned') NOT NULL DEFAULT 'for_review'");
        }
    }
};
