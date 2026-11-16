<?php

namespace Database\Seeders;

use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\FacilityStaff;
use App\Models\Profiles\LogisticsFacility;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Creates one demo Logistics Company and its province/municipality facilities per PSGC area. */
class PhilippinesLogisticsCompanySeeder extends Seeder
{
    public const PASSWORD = 'password123';

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(resource_path('data/psgc-locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $passwordHash = Hash::make(self::PASSWORD);

        foreach ($catalog as $provinceCode => $province) {
            $provinceName = $province['name'];
            $municipality = array_values($province['cities'])[0] ?? $provinceName;
            $slug = Str::slug(Str::ascii($provinceName));
            $email = "logistics.{$slug}@vendo.com";
            $businessName = "Vendo Logistics - {$provinceName}";

            $legacyAccount = User::query()->where('email', "logistics.{$slug}@vendo.test")->first();
            if ($legacyAccount && ! User::query()->where('email', $email)->exists()) {
                $legacyAccount->update(['email' => $email]);
            }

            $user = User::updateOrCreate(['email' => $email], [
                'name' => "{$provinceName} Logistics Company",
                'password' => $passwordHash,
                'role' => 'logistics_center',
                'status' => 'approved',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]);

            $center = LogisticsCenter::updateOrCreate(['user_id' => $user->id], [
                'last_name' => 'Demo', 'first_name' => 'Logistics', 'middle_name' => null,
                'sex' => 'male', 'birthday' => '1990-01-01', 'valid_id_path' => null,
                'province' => $provinceName, 'municipality' => $municipality,
                'barangay' => 'Demo Barangay', 'street' => null, 'house_no' => null,
                'zip_code' => null, 'business_name' => $businessName,
                'business_permit_path' => 'private/demo/logistics-business-permit.pdf',
                'coverage_province_code' => (string) $provinceCode,
                'coverage_province' => $provinceName,
            ]);

            LogisticsCompany::updateOrCreate(['logistics_center_id' => $center->id], [
                'business_name' => $businessName,
                'status' => 'approved',
            ]);

            $company = LogisticsCompany::where('logistics_center_id', $center->id)->firstOrFail();
            $provinceHub = $company->facilities()->updateOrCreate(
                ['kind' => 'province_hub', 'province_code' => (string) $provinceCode],
                [
                    'logistics_center_id' => $center->id,
                    'parent_id' => null,
                    'name' => "{$provinceName} Province Hub",
                    'province_hub_key' => (string) $provinceCode,
                    'province' => $provinceName,
                    'municipality_code' => null,
                    'municipality' => $provinceName,
                    'address' => "{$provinceName} Province Hub",
                    'is_active' => true,
                ]
            );
            $provinceManager = $this->managerAccount("province.{$slug}@vendo.com", "{$provinceName} Province Hub Manager", 'province_hub', $passwordHash);
            FacilityStaff::updateOrCreate(['facility_id' => $provinceHub->id], [
                'user_id' => $provinceManager->id,
                'staff_title' => 'province_manager',
            ]);

            foreach ($province['cities'] as $municipalityCode => $municipalityName) {
                $station = $company->facilities()->updateOrCreate(
                    ['kind' => 'station_hub', 'municipality_code' => (string) $municipalityCode],
                    [
                        'logistics_center_id' => $center->id,
                        'parent_id' => $provinceHub->id,
                        'name' => "{$municipalityName} Station Hub",
                        'province_code' => (string) $provinceCode,
                        'province' => $provinceName,
                        'municipality_code' => (string) $municipalityCode,
                        'municipality' => $municipalityName,
                        'station_municipality_key' => (string) $municipalityCode,
                        'address' => "{$municipalityName}, {$provinceName}",
                        'is_active' => true,
                    ]
                );
                $stationSlug = Str::slug(Str::ascii($municipalityName));
                $stationManager = $this->managerAccount("station.{$slug}.{$stationSlug}@vendo.com", "{$municipalityName} Station Hub Manager", 'station_hub', $passwordHash);
                FacilityStaff::updateOrCreate(['facility_id' => $station->id], [
                    'user_id' => $stationManager->id,
                    'staff_title' => 'station_manager',
                ]);
            }
        }
    }

    private function managerAccount(string $email, string $name, string $role, string $passwordHash): User
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'first_name' => Str::before($name, ' '),
            'last_name' => 'Manager',
            'password' => $passwordHash,
            'role' => $role,
            'status' => 'approved',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
