<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The Company whose Station Hubs carry this parcel. Null for orders placed before
            // the Buyer could choose a Logistics provider; routing falls back to the address match.
            $table->foreignId('delivery_company_id')->nullable()->after('logistics_center_id')
                ->constrained('logistics_companies')->nullOnDelete();
            $table->index(['delivery_company_id', 'status'], 'orders_delivery_company_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_delivery_company_status');
            $table->dropConstrainedForeignId('delivery_company_id');
        });
    }
};
