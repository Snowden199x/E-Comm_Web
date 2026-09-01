<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY status ENUM('for_review', 'approved', 'rejected', 'warned') DEFAULT 'for_review'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY status ENUM('for_review', 'approved', 'rejected') DEFAULT 'for_review'");
    }
};