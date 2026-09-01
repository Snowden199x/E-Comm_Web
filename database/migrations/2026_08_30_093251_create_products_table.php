<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('brand')->nullable();
            $table->string('material')->nullable();
            $table->string('sizes')->nullable();
            $table->string('colors')->nullable();
            $table->string('weight')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->enum('status', ['for_review', 'approved', 'rejected'])->default('for_review');
            $table->string('rejection_reason')->nullable();
            $table->text('rejection_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};