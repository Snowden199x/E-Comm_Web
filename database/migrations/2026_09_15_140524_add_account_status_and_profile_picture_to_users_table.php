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
        $table->enum('account_status', ['active', 'suspended', 'deactivated'])
            ->default('active')
            ->after('is_super_admin');
        $table->string('profile_picture')->nullable()->after('account_status');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['account_status', 'profile_picture']);
    });
}
};
