<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seller sale window: a percentage off or a fixed peso amount off the listed
     * price between two dates. The listed price stays untouched for reporting.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sale_type', 10)->nullable()->after('compare_at_price');
            $table->decimal('sale_value', 10, 2)->nullable()->after('sale_type');
            $table->timestamp('sale_starts_at')->nullable()->after('sale_value');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_type', 'sale_value', 'sale_starts_at', 'sale_ends_at']);
        });
    }
};
