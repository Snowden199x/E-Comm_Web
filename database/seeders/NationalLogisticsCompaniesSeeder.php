<?php

namespace Database\Seeders;

use App\Models\Profiles\FacilityStaff;
use App\Models\Profiles\LogisticsCompany;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Seeds the three nationwide Logistics demo companies and their PSGC hub networks. */
class NationalLogisticsCompaniesSeeder extends Seeder
{
    public const PASSWORD = 'password123';

    public const COMPANIES = [
        'express' => 'Vendo Express',
        'flash' => 'Vendo Flash',
        'move' => 'Vendo Move',
    ];

    public static function companyEmail(string $companyKey): string
    {
        return "logistics.{$companyKey}@vendo.com";
    }

    public static function provinceManagerEmail(string $companyKey, string $provinceCode): string
    {
        return "province.{$companyKey}.{$provinceCode}@vendo.com";
    }

    public static function stationManagerEmail(string $companyKey, string $provinceCode, string $municipalityCode): string
    {
        return "station.{$companyKey}.{$provinceCode}.{$municipalityCode}@vendo.com";
    }

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(resource_path('data/psgc-locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $passwordHash = Hash::make(self::PASSWORD);
        $expressPrimaryRows = [];

        foreach (self::COMPANIES as $companyKey => $businessName) {
            $company = $this->seedCompany($companyKey, $businessName, $passwordHash);

            foreach ($catalog as $provinceCode => $province) {
                $provinceCode = (string) $provinceCode;
                $provinceName = $province['name'];
                $provinceHub = $company->facilities()->updateOrCreate(
                    ['kind' => 'province_hub', 'province_code' => $provinceCode],
                    [
                        'logistics_center_id' => $company->logistics_center_id,
                        'parent_id' => null,
                        'name' => "{$businessName} - {$provinceName} Province Hub",
                        'province_hub_key' => $provinceCode,
                        'province' => $provinceName,
                        'municipality_code' => null,
                        'municipality' => $provinceName,
                        'address' => "{$provinceName} Province Hub",
                        'is_active' => true,
                    ]
                );

                $provinceManager = $this->managerAccount(
                    self::provinceManagerEmail($companyKey, $provinceCode),
                    "{$businessName} {$provinceName} Province Hub Manager",
                    'province_hub',
                    $passwordHash,
                );
                FacilityStaff::updateOrCreate(['facility_id' => $provinceHub->id], [
                    'user_id' => $provinceManager->id,
                    'staff_title' => 'province_manager',
                ]);

                foreach ($province['cities'] as $municipalityCode => $municipalityName) {
                    $municipalityCode = (string) $municipalityCode;
                    $station = $company->facilities()->updateOrCreate(
                        ['kind' => 'station_hub', 'municipality_code' => $municipalityCode],
                        [
                            'logistics_center_id' => $company->logistics_center_id,
                            'parent_id' => $provinceHub->id,
                            'name' => "{$businessName} - {$municipalityName}, {$provinceName} Station Hub",
                            'province_code' => $provinceCode,
                            'province' => $provinceName,
                            'municipality_code' => $municipalityCode,
                            'municipality' => $municipalityName,
                            'station_municipality_key' => $municipalityCode,
                            'address' => "{$municipalityName}, {$provinceName}",
                            'is_active' => true,
                        ]
                    );

                    $stationManager = $this->managerAccount(
                        self::stationManagerEmail($companyKey, $provinceCode, $municipalityCode),
                        "{$businessName} {$municipalityName} Station Hub Manager",
                        'station_hub',
                        $passwordHash,
                    );
                    FacilityStaff::updateOrCreate(['facility_id' => $station->id], [
                        'user_id' => $stationManager->id,
                        'staff_title' => 'station_manager',
                    ]);

                    if ($companyKey === 'express') {
                        $expressPrimaryRows[] = [
                            'province_code' => $provinceCode,
                            'municipality_code' => $municipalityCode,
                            'station_hub_id' => $station->id,
                            'assigned_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // Generic and legacy address matching has one primary station per municipality.
        // Buyers and Riders who choose a company still resolve against that company's own network.
        foreach (array_chunk($expressPrimaryRows, 200) as $rows) {
            DB::table('primary_logistics_coverage')->upsert(
                $rows,
                ['province_code', 'municipality_code'],
                ['station_hub_id', 'assigned_by', 'updated_at'],
            );
        }

        $this->deletePreviousProvinceDemoNetwork($catalog);
    }

    private function seedCompany(string $companyKey, string $businessName, string $passwordHash): LogisticsCompany
    {
        $user = User::updateOrCreate(['email' => self::companyEmail($companyKey)], [
            'name' => $businessName,
            'first_name' => 'Vendo',
            'last_name' => Str::after($businessName, 'Vendo '),
            'password' => $passwordHash,
            'role' => 'logistics_center',
            'status' => 'approved',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'archived_at' => null,
        ]);

        $center = LogisticsCenter::updateOrCreate(['user_id' => $user->id], [
            'last_name' => 'Demo',
            'first_name' => 'Vendo',
            'middle_name' => null,
            'sex' => 'male',
            'birthday' => '1990-01-01',
            'valid_id_path' => null,
            'province' => 'Metro Manila',
            'municipality' => 'City of Manila',
            'barangay' => 'Demo Barangay',
            'street' => 'Demo National Office',
            'house_no' => null,
            'zip_code' => null,
            'business_name' => $businessName,
            'business_permit_path' => 'private/demo/logistics-business-permit.pdf',
            'coverage_province_code' => null,
            'coverage_province' => null,
        ]);

        return LogisticsCompany::updateOrCreate(['logistics_center_id' => $center->id], [
            'business_name' => $businessName,
            'status' => 'approved',
        ]);
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
            'archived_at' => null,
        ]);
    }

    /** Delete only the deterministic accounts made by the former one-company-per-province seeder. */
    private function deletePreviousProvinceDemoNetwork(array $catalog): void
    {
        $companyEmails = [];
        $managerEmails = [];

        foreach ($catalog as $province) {
            $provinceSlug = Str::slug(Str::ascii($province['name']));
            $companyEmails[] = "logistics.{$provinceSlug}@vendo.com";
            $companyEmails[] = "logistics.{$provinceSlug}@vendo.test";
            $managerEmails[] = "province.{$provinceSlug}@vendo.com";

            foreach ($province['cities'] as $municipalityName) {
                $municipalitySlug = Str::slug(Str::ascii($municipalityName));
                $managerEmails[] = "station.{$provinceSlug}.{$municipalitySlug}@vendo.com";
            }
        }

        $legacyCompanies = User::query()->whereIn('email', array_unique($companyEmails))
            ->where('role', 'logistics_center')->get();
        $legacyManagers = User::query()->whereIn('email', array_unique($managerEmails))
            ->whereIn('role', ['province_hub', 'station_hub'])->get();

        DB::transaction(function () use ($legacyCompanies, $legacyManagers): void {
            // Deleting the Company owner cascades to its Logistics Center, Company and facilities.
            // Protected operational references abort this cleanup transaction instead of being forced away.
            foreach ($legacyCompanies as $legacyCompany) {
                $legacyCompany->delete();
            }

            foreach ($legacyManagers as $legacyManager) {
                $legacyManager->delete();
            }
        });
    }
}
