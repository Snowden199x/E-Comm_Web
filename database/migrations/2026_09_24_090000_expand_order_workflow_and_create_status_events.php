<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->string('status', 40)->default('placed')->change());
        foreach (['pending' => 'placed', 'to_ship' => 'confirmed', 'in_transit' => 'picked_up'] as $old => $new)
            DB::table('orders')->where('status', $old)->update(['status' => $new]);
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 40)->nullable(); $table->string('to_status', 40);
            $table->string('note', 500)->nullable(); $table->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['pending' => ['placed'], 'to_ship' => ['confirmed','preparing','ready_for_pickup'],
            'in_transit' => ['picked_up','at_sorting_center','sorted','assigned_to_rider','out_for_delivery','delivery_failed'],
            'delivered' => ['completed']] as $old => $stages) DB::table('orders')->whereIn('status', $stages)->update(['status' => $old]);
        Schema::dropIfExists('order_status_events');
        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending','to_ship','in_transit','out_for_delivery','delivered','cancelled','returned'])->default('pending')->change());
    }
};
