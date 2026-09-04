<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('complaints', function (Blueprint $table) {
        $table->string('type')->nullable()->after('respondent_id');
    });
}

public function down(): void
{
    Schema::table('complaints', function (Blueprint $table) {
        $table->dropColumn('type');
    });
}
};
