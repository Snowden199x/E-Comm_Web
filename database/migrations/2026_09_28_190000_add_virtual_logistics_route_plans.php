<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_route_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('province');
            $table->string('municipality');
            $table->string('province_code', 9)->nullable();
            $table->string('municipality_code', 9)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(
                ['province', 'municipality', 'is_active'],
                'log_route_checkpoint_place_active_idx'
            );
        });

        // Suggested virtual locality labels from the agreed Manila/Laguna
        // examples. They are inactive templates, not registered facilities.
        $now = now();
        foreach ([
            ['SH5', 'Muntinlupa', 'Metro Manila', 'City of Muntinlupa', '130000000', '137603000'],
            ['SH6', 'Calamba', 'Laguna', 'City of Calamba', '043400000', '043405000'],
            ['SH3', 'Pagsanjan', 'Laguna', 'Pagsanjan', '043400000', '043419000'],
        ] as [$code, $name, $province, $municipality, $provinceCode, $municipalityCode]) {
            DB::table('logistics_route_checkpoints')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'province' => $province,
                    'municipality' => $municipality,
                    'province_code' => $provinceCode,
                    'municipality_code' => $municipalityCode,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        Schema::create('logistics_route_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_logistics_center_id')->constrained('logistics_centers')->cascadeOnDelete();
            $table->foreignId('to_logistics_center_id')->constrained('logistics_centers')->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['from_logistics_center_id', 'to_logistics_center_id', 'priority'], 'logistics_route_plans_pair_priority_unique');
        });

        Schema::create('logistics_route_plan_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_plan_id')->constrained('logistics_route_plans')->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained('logistics_route_checkpoints')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['route_plan_id', 'position'], 'logistics_route_plan_stops_position_unique');
            $table->unique(['route_plan_id', 'checkpoint_id'], 'logistics_route_plan_stops_checkpoint_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('route_plan_id')->nullable()->constrained('logistics_route_plans')->nullOnDelete();
            $table->foreignId('next_route_checkpoint_id')->nullable()->constrained('logistics_route_checkpoints')->nullOnDelete();
            $table->unsignedSmallInteger('route_step')->nullable();
        });

        // Preserve any checkpoint records created through the earlier draft
        // registry, but the owner/account and physical address are not used.
        if (Schema::hasTable('logistics_sub_hubs')) {
            DB::table('logistics_sub_hubs')->orderBy('id')->get()->each(function ($hub) {
                DB::table('logistics_route_checkpoints')->updateOrInsert(
                    ['code' => $hub->code],
                    [
                        'name' => $hub->name,
                        'province' => $hub->province,
                        'municipality' => $hub->municipality,
                        'province_code' => $hub->province_code,
                        'municipality_code' => $hub->municipality_code,
                        // A legacy facility's activation must not activate a
                        // newly reinterpreted virtual locality automatically.
                        'is_active' => false,
                        'created_at' => $hub->created_at,
                        'updated_at' => $hub->updated_at,
                    ]
                );
            });

            if (Schema::hasColumn('orders', 'next_sub_hub_id')) {
                DB::table('orders')->whereNotNull('next_sub_hub_id')->orderBy('id')->get()->each(function ($order) {
                    $hub = DB::table('logistics_sub_hubs')->where('id', $order->next_sub_hub_id)->first();
                    $checkpoint = $hub
                        ? DB::table('logistics_route_checkpoints')->where('code', $hub->code)->first()
                        : null;

                    if ($checkpoint) {
                        DB::table('orders')->where('id', $order->id)->update([
                            'next_route_checkpoint_id' => $checkpoint->id,
                        ]);
                    }
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_route_checkpoint_id');
            $table->dropConstrainedForeignId('route_plan_id');
            $table->dropColumn('route_step');
        });

        Schema::dropIfExists('logistics_route_plan_stops');
        Schema::dropIfExists('logistics_route_plans');
        Schema::dropIfExists('logistics_route_checkpoints');
    }
};
