<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buyer-saved delivery addresses. The registered address in buyer_details stays
     * the account default; this table lets a Buyer keep and reuse several addresses.
     */
    public function up(): void
    {
        Schema::create('buyer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->default('Home');
            $table->string('house_no', 60)->nullable();
            $table->string('street', 180);
            $table->string('barangay', 120);
            $table->string('municipality', 120);
            $table->string('province', 120);
            $table->string('zip_code', 12)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default'], 'buyer_addresses_user_default_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_addresses');
    }
};
