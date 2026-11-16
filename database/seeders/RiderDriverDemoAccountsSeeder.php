<?php

namespace Database\Seeders;

use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\RiderServiceArea;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use App\Services\LocationCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds five Rider (courier) and five Transport Driver demo accounts for each
 * requested city. The province, city and barangay codes all come from the same
 * bundled PSGC catalog the Rider and Driver registration APIs validate against,
 * and every account is attached to a Station Hub for that city.
 *
 * The network is reused when it exists: Vendo Express is preferred when the
 * nationwide demo network is present, otherwise the first approved Company with
 * a Station Hub becomes the host. Missing Province and Station Hubs for the
 * seeded cities are created under that host, so the accounts always point at a
 * real, active hub instead of a dangling facility id.
 *
 * "Bacoor, Cavite" and "Bacoor City, Cavite" are the same PSGC municipality
 * (City of Bacoor, 042103000), so the place is seeded once rather than twice.
 *
 *   php artisan db:seed --class=RiderDriverDemoAccountsSeeder
 */
class RiderDriverDemoAccountsSeeder extends Seeder
{
    public const PASSWORD = NationalLogisticsCompaniesSeeder::PASSWORD;

    /** Demo document paths; the files are placeholders, not real uploads. */
    private const DRIVERS_LICENSE_PATH = 'private/demo/rider-drivers-license.jpg';

    private const OR_CR_PATH = 'private/demo/rider-or-cr.jpg';

    private const VALID_ID_PATH = 'private/demo/transport-driver-license.jpg';

    /** Province/city/barangay codes are PSGC codes from resources/data/. */
    public const PLACES = [
        'manila' => [
            'label' => 'Manila, Metro Manila',
            'province_code' => '130000000',
            'city_code' => '133900000',
            'zip_code' => '1000',
            'barangays' => [
                ['133901001', 'Barangay 1'],
                ['133901002', 'Barangay 2'],
                ['133901003', 'Barangay 3'],
                ['133901004', 'Barangay 4'],
                ['133901005', 'Barangay 5'],
            ],
        ],
        'tagaytay' => [
            'label' => 'Tagaytay City, Cavite',
            'province_code' => '042100000',
            'city_code' => '042119000',
            'zip_code' => '4120',
            'barangays' => [
                ['042119001', 'Asisan'],
                ['042119002', 'Bagong Tubig'],
                ['042119005', 'Guinhawa South'],
                ['042119018', 'San Jose'],
                ['042119036', 'Sungay North'],
            ],
        ],
        'bacoor' => [
            'label' => 'Bacoor City, Cavite',
            'province_code' => '042100000',
            'city_code' => '042103000',
            'zip_code' => '4102',
            'barangays' => [
                ['042103004', 'Bayanan'],
                ['042103008', 'Habay I'],
                ['042103010', 'Ligas I'],
                ['042103014', 'Molino I'],
                ['042103025', 'Zapote I'],
            ],
        ],
        'batangas-city' => [
            'label' => 'Batangas City, Batangas',
            'province_code' => '041000000',
            'city_code' => '041005000',
            'zip_code' => '4200',
            'barangays' => [
                ['041005001', 'Alangilan'],
                ['041005003', 'Balagtas'],
                ['041005012', 'Bolbok'],
                ['041005020', 'Cuta'],
                ['041005051', 'Pallocan Kanluran'],
            ],
        ],
        'cebu-city' => [
            'label' => 'Cebu City, Cebu',
            'province_code' => '072200000',
            'city_code' => '072217000',
            'zip_code' => '6000',
            'barangays' => [
                ['072217007', 'Banilad'],
                ['072217029', 'Guadalupe'],
                ['072217041', 'Lahug (Pob.)'],
                ['072217046', 'Mabolo'],
                ['072217081', 'Talamban'],
            ],
        ],
    ];

    private const MALE_NAMES = ['Miguel', 'Jose', 'Rafael', 'Paolo', 'Dennis'];

    private const FEMALE_NAMES = ['Andrea', 'Bea', 'Liza', 'Camille', 'Grace'];

    private const LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia',
        'Mendoza', 'Torres', 'Aquino', 'Ramos', 'Villanueva',
    ];

    private const STREETS = [
        '12 Rizal Street', '48 Mabini Street', '7 Bonifacio Street',
        '25 Luna Street', '31 Del Pilar Street',
    ];

    public function run(): void
    {
        $locations = app(LocationCatalog::class);
        $passwordHash = Hash::make(self::PASSWORD);
        $company = $this->company();

        $summary = DB::transaction(function () use ($locations, $passwordHash, $company) {
            $summary = [];
            $sequence = 0;

            foreach (self::PLACES as $placeKey => $place) {
                $address = $locations->address($place['province_code'], $place['city_code']);
                if (! $address) {
                    throw new \RuntimeException("PSGC city code {$place['city_code']} for {$place['label']} is not in the location catalog.");
                }

                [$provinceHub, $stationHub] = $this->hubs($company, $place, $address);

                foreach ($place['barangays'] as $index => [$barangayCode, $barangayName]) {
                    if ($locations->barangayName($place['city_code'], $barangayCode) === null) {
                        throw new \RuntimeException("PSGC barangay code {$barangayCode} does not belong to {$address['city']}.");
                    }

                    $sequence++;
                    $sex = $index % 2 === 0 ? 'male' : 'female';
                    $firstName = ($sex === 'male' ? self::MALE_NAMES : self::FEMALE_NAMES)[$index];
                    $lastName = self::LAST_NAMES[$sequence % count(self::LAST_NAMES)];

                    $this->seedRider(
                        $place, $placeKey, $address, $company, $stationHub,
                        $barangayCode, $barangayName, $index, $sequence,
                        $firstName, $lastName, $sex, $passwordHash,
                    );
                    $this->seedDriver(
                        $place, $placeKey, $address, $provinceHub, $stationHub,
                        $barangayName, $index, $sequence,
                        $firstName, $lastName, $sex, $passwordHash,
                    );

                    $summary[$placeKey]['label'] = $place['label'];
                    $summary[$placeKey]['station'] = $stationHub->name;
                    $summary[$placeKey]['riders'][] = $this->email('rider', $placeKey, $index);
                    $summary[$placeKey]['drivers'][] = $this->email('driver', $placeKey, $index);
                }
            }

            return $summary;
        });

        foreach ($summary as $placeKey => $rows) {
            $this->command?->info("{$rows['label']} → {$rows['station']}");
            $this->command?->line('  Riders: '.implode(', ', $rows['riders']));
            $this->command?->line('  Drivers: '.implode(', ', $rows['drivers']));
        }
        $this->command?->info('Rider and Transport Driver demo accounts are approved and ready to sign in.');
        $this->command?->line('Password for every account: '.self::PASSWORD);
    }

    private function company(): LogisticsCompany
    {
        $company = LogisticsCompany::query()
            ->where('business_name', NationalLogisticsCompaniesSeeder::COMPANIES['express'])
            ->where('status', 'approved')
            ->first();

        $company ??= LogisticsCompany::query()
            ->where('status', 'approved')
            ->whereHas('facilities', fn ($query) => $query->where('kind', 'station_hub'))
            ->orderBy('id')
            ->first();

        if (! $company) {
            throw new \RuntimeException(
                'No approved Logistics Company with a Station Hub network was found. '
                .'Run: php artisan db:seed --class=NationalLogisticsCompaniesSeeder'
            );
        }

        return $company;
    }

    /**
     * Reuses the host Company's Province and Station Hubs for this city, creating
     * them when the local network does not reach the seeded city yet.
     *
     * @return array{0: LogisticsFacility, 1: LogisticsFacility}
     */
    private function hubs(LogisticsCompany $company, array $place, array $address): array
    {
        $provinceHub = $company->facilities()->firstOrCreate(
            ['kind' => 'province_hub', 'province_code' => $place['province_code']],
            [
                'logistics_center_id' => $company->logistics_center_id,
                'parent_id' => null,
                'name' => "{$company->business_name} - {$address['province']} Province Hub",
                'province_hub_key' => $place['province_code'],
                'province' => $address['province'],
                'municipality_code' => null,
                'municipality' => $address['province'],
                'address' => "{$address['province']} Province Hub",
                'is_active' => true,
            ],
        );
        $stationHub = $company->facilities()->firstOrCreate(
            ['kind' => 'station_hub', 'municipality_code' => $place['city_code']],
            [
                'logistics_center_id' => $company->logistics_center_id,
                'parent_id' => $provinceHub->id,
                'name' => "{$company->business_name} - {$address['city']}, {$address['province']} Station Hub",
                'province_code' => $place['province_code'],
                'province' => $address['province'],
                'municipality' => $address['city'],
                'station_municipality_key' => $place['city_code'],
                'address' => "{$address['city']}, {$address['province']}",
                'is_active' => true,
            ],
        );

        foreach ([$provinceHub, $stationHub] as $hub) {
            if (! $hub->is_active) {
                $hub->update(['is_active' => true]);
            }
        }

        return [$provinceHub, $stationHub];
    }

    private function seedRider(
        array $place,
        string $placeKey,
        array $address,
        LogisticsCompany $company,
        LogisticsFacility $stationHub,
        string $barangayCode,
        string $barangayName,
        int $index,
        int $sequence,
        string $firstName,
        string $lastName,
        string $sex,
        string $passwordHash,
    ): void {
        $email = $this->email('rider', $placeKey, $index);
        $rider = User::updateOrCreate(['email' => $email], [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $passwordHash,
            'phone_number' => $this->phoneNumber('0917', $sequence),
            'role' => 'courier',
            'status' => 'approved',
            'account_status' => 'active',
            'archived_at' => null,
        ]);
        $rider->forceFill(['email_verified_at' => now()])->save();

        $detail = CourierDetail::updateOrCreate(['user_id' => $rider->id], [
            'logistics_center_id' => $company->logistics_center_id,
            'station_hub_id' => $stationHub->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => null,
            'sex' => $sex,
            'birthday' => '1996-0'.(($index % 9) + 1).'-15',
            'valid_id_path' => self::DRIVERS_LICENSE_PATH,
            'province' => $address['province'],
            'municipality' => $address['city'],
            'barangay' => $barangayName,
            'street' => self::STREETS[$index],
            'house_no' => null,
            'zip_code' => $place['zip_code'],
            'vehicle_type' => 'Motorcycle',
            'plate_number' => 'RDR '.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'drivers_license_path' => self::DRIVERS_LICENSE_PATH,
            'or_cr_path' => self::OR_CR_PATH,
        ]);

        RiderServiceArea::updateOrCreate(
            ['courier_detail_id' => $detail->id, 'barangay_code' => $barangayCode],
            ['station_hub_id' => $stationHub->id, 'barangay' => $barangayName],
        );
    }

    private function seedDriver(
        array $place,
        string $placeKey,
        array $address,
        LogisticsFacility $provinceHub,
        LogisticsFacility $stationHub,
        string $barangayName,
        int $index,
        int $sequence,
        string $firstName,
        string $lastName,
        string $sex,
        string $passwordHash,
    ): void {
        $driverUser = User::updateOrCreate(['email' => $this->email('driver', $placeKey, $index)], [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $passwordHash,
            'phone_number' => $this->phoneNumber('0918', $sequence),
            'role' => 'transport_driver',
            'status' => 'approved',
            'account_status' => 'active',
            'archived_at' => null,
        ]);
        $driverUser->forceFill(['email_verified_at' => now()])->save();

        TransportDriverDetail::updateOrCreate(['user_id' => $driverUser->id], [
            'province_hub_id' => $provinceHub->id,
            'apply_to' => TransportDriverDetail::APPLY_PROVINCE,
            'station_hub_id' => $stationHub->id,
            'license_number' => 'N01-23-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'license_category' => 'Professional',
            'license_expires_at' => now()->addYears(3),
            'drivers_license_path' => self::VALID_ID_PATH,
            'valid_id_path' => self::VALID_ID_PATH,
            'status' => 'available',
            'approved_by' => $provinceHub->staff?->user_id,
            'approved_at' => now(),
            'birthday' => '1994-0'.(($index % 9) + 1).'-20',
            'sex' => $sex,
            'home_address' => implode(', ', [
                self::STREETS[$index], $barangayName, $address['city'], $address['province'],
            ]),
            'vehicle_type' => 'Van',
            'plate_number' => 'DRV '.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
        ]);
    }

    private function email(string $role, string $placeKey, int $index): string
    {
        return "{$role}.{$placeKey}.".($index + 1).'@vendo.test';
    }

    private function phoneNumber(string $prefix, int $sequence): string
    {
        return $prefix.str_pad((string) (3000000 + $sequence), 7, '0', STR_PAD_LEFT);
    }
}
