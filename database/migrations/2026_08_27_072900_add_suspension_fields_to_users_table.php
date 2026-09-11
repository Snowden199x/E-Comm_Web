<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only ENUM modification skipped for SQLite compatibility.
        // Status values are enforced at the application/model level instead.

        Schema::table('users', function (Blueprint $table) {
            $table->string('suspension_reason')->nullable()->after('rejection_notes');
            $table->text('suspension_notes')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_at')->nullable()->after('suspension_notes');
            $table->timestamp('suspended_until')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspension_reason', 'suspension_notes', 'suspended_at', 'suspended_until']);
        });
    }
};