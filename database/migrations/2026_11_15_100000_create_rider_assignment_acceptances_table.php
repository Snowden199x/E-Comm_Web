<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_assignment_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('assignment', 16)->default('pickup');
            $table->timestamp('accepted_at');
            $table->timestamps();
            // One answer per Rider and parcel, so a reassignment or a new task
            // for the same pickup does not ask the Rider to accept twice.
            $table->unique(['rider_id', 'order_id', 'assignment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_assignment_acceptances');
    }
};
