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
    Schema::table('users', function (Blueprint $table) {
        $table->string('first_name')->nullable()->after('name');
        $table->string('last_name')->nullable()->after('first_name');
        $table->string('middle_initial', 5)->nullable()->after('last_name');
        $table->boolean('must_change_password')->default(false)->after('is_super_admin');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['first_name', 'last_name', 'middle_initial', 'must_change_password']);
    });
}
};
