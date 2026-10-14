<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_centers', function (Blueprint $table) {
            $table->string('coverage_province_code', 9)->nullable()->unique('lc_coverage_province_unique');
            $table->string('coverage_province')->nullable();
        });

        Schema::create('logistics_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_center_id')->constrained('logistics_centers')->cascadeOnDelete();
            $table->string('kind', 16);
            // NULL for stations; a unique non-NULL value enforces one Main Hub per network.
            $table->unsignedBigInteger('main_hub_owner_id')->nullable()->unique('lf_one_main_hub');
            $table->string('name');
            $table->string('province_code', 9)->nullable();
            $table->string('province');
            $table->string('municipality_code', 9)->nullable();
            $table->string('municipality');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['logistics_center_id', 'kind'], 'lf_owner_kind');
            $table->unique(['logistics_center_id', 'name'], 'lf_owner_name_unique');
        });

        $catalog = json_decode(file_get_contents(resource_path('data/psgc-locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $normalized = static function (string $name): string {
            $key = preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($name)));

            return in_array($key, ['ncr', 'nationalcapitalregion'], true) ? 'metromanila' : $key;
        };
        $provinceCodes = [];
        foreach ($catalog as $code => $province) {
            $provinceCodes[$normalized($province['name'])] = [$code, $province['name']];
        }
        $claims = [];
        $centers = DB::table('logistics_centers')->join('users', 'users.id', '=', 'logistics_centers.user_id')
            ->select('logistics_centers.*', 'users.status as registration_status')->orderBy('logistics_centers.id')->get();
        foreach ($centers as $center) {
            $matched = $provinceCodes[$normalized($center->province)] ?? null;
            if ($matched && $center->registration_status !== 'disapproved') {
                $claims[$matched[0]][] = $center->id;
            }
            DB::table('logistics_facilities')->insert([
                'logistics_center_id' => $center->id,
                'kind' => 'main_hub',
                'main_hub_owner_id' => $center->id,
                'name' => $center->business_name.' Main Hub',
                'province_code' => $matched[0] ?? null,
                'province' => $center->province,
                'municipality' => $center->municipality,
                'address' => trim(implode(', ', array_filter([$center->house_no, $center->street, $center->barangay]))),
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($claims as $code => $owners) {
            if (count($owners) === 1) {
                DB::table('logistics_centers')->where('id', $owners[0])->update([
                    'coverage_province_code' => $code,
                    'coverage_province' => $catalog[$code]['name'],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_facilities');
        Schema::table('logistics_centers', function (Blueprint $table) {
            $table->dropUnique('lc_coverage_province_unique');
            $table->dropColumn(['coverage_province_code', 'coverage_province']);
        });
    }
};
