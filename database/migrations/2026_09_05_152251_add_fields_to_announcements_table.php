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
    Schema::table('announcements', function (Blueprint $table) {
        $table->string('audience')->default('All Users')->after('message');
        $table->timestamp('scheduled_at')->nullable()->after('audience');
        $table->enum('status', ['draft', 'scheduled', 'published'])->default('draft')->after('scheduled_at');
    });
}

public function down(): void
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->dropColumn(['audience', 'scheduled_at', 'status']);
    });
}
};
