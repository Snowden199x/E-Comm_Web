<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyer_details', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('zip_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->foreignId('preferred_delivery_company_id')->nullable()->after('longitude')
                ->constrained('logistics_companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('buyer_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_delivery_company_id');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
