<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `role` column exists on `users` as a MySQL ENUM, but it was
     * never actually defined in any migration in this codebase — it
     * must have been created directly on the database at some point
     * and its allowed values don't include 'admin' (that's why seeding
     * an admin fails with "Data truncated for column 'role'": MySQL
     * rejects any value that isn't one of the ENUM's fixed options).
     *
     * Widening it to a plain VARCHAR removes that fixed list entirely,
     * so 'admin', 'courier', or any future role string will always be
     * accepted — the application code, not the schema, decides which
     * roles are valid.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('buyer')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['buyer', 'seller'])->default('buyer')->change();
        });
    }
};
