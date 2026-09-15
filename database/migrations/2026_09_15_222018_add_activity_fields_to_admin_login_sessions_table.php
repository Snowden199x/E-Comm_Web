<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_login_sessions', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('login_at');
            $table->timestamp('logged_out_at')->nullable()->after('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_login_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'logged_out_at',
            ]);
        });
    }
};