<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Station staff resolve a cash discrepancy with a note; kept apart from the receipt note.
        Schema::table('cash_remittances', function (Blueprint $table) {
            $table->text('resolution_note')->nullable()->after('note');
            $table->timestamp('resolved_at')->nullable()->after('verified_at');
            $table->foreignId('resolved_by')->nullable()->after('verified_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_remittances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['resolution_note', 'resolved_at']);
        });
    }
};
