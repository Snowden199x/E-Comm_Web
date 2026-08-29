<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'approved', 'disapproved', 'suspended', 'deactivated') NOT NULL DEFAULT 'pending'");

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

        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'approved', 'disapproved') NOT NULL DEFAULT 'pending'");
    }
};