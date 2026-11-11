<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products are soft deleted so order history, reviews and stock movements keep their product row.
     * Seller-facing queries already read the default scope, so a deleted product disappears from the
     * catalog, cart and shop without breaking past orders.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['seller_id', 'deleted_at'], 'products_seller_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_seller_deleted_idx');
            $table->dropSoftDeletes();
        });
    }
};
