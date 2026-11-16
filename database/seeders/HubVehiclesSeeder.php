<?php

namespace Database\Seeders;

use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsCompanyVehicle;
use App\Models\Profiles\LogisticsFacility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a small company fleet for the Province Hubs that cover the demo cities,
 * so Province Hub > Vehicles, trip assignment and handoff manifests have real
 * vehicles to choose from.
 *
 * Vehicles belong to a Province Hub in this schema: `company_vehicles` has a
 * required `province_hub_id` and no Station Hub column, so the Station Hubs of a
 * city use the vehicles of their Province Hub. The fleet is seeded on the same
 * host Company as the Rider and Transport Driver demo accounts, and the hubs
 * themselves come from RiderDriverDemoAccountsSeeder, so run that first.
 *
 * Every vehicle is deterministic and idempotent through updateOrCreate, using
 * the same code/plate format the Province Hub form accepts: codes such as
 * TRK-001 and plates such as VND 1001 (three letters, four digits). Capacities
 * are stored in kilograms, which the page shows as tons. The last vehicle of
 * each hub is left in maintenance with a note, so the "Back in service" action
 * has something to work with.
 *
 *   php artisan db:seed --class=HubVehiclesSeeder
 */
class HubVehiclesSeeder extends Seeder
{
    /** Vehicle types come from the Province Hub > Vehicles form options. */
    private const FLEET = [
        ['type' => 'L300 Van', 'capacity_kg' => 1200],
        ['type' => 'Closed Van', 'capacity_kg' => 2500],
        ['type' => '6-Wheeler Truck', 'capacity_kg' => 8000],
        ['type' => '10-Wheeler', 'capacity_kg' => 15000],
    ];

    private const MAINTENANCE_NOTE = 'Scheduled preventive maintenance: brake pads and tyre rotation.';

    public function run(): void
    {
        $company = $this->company();
        $provinceCodes = collect(RiderDriverDemoAccountsSeeder::PLACES)
            ->pluck('province_code')
            ->unique()
            ->values();

        $summary = DB::transaction(function () use ($company, $provinceCodes) {
            $summary = [];
            $sequence = 0;

            foreach ($provinceCodes as $provinceCode) {
                $hub = $company->facilities()
                    ->where('kind', 'province_hub')
                    ->where('province_code', $provinceCode)
                    ->first();

                if (! $hub) {
                    throw new \RuntimeException(
                        "No Province Hub for PSGC province {$provinceCode} under {$company->business_name}. "
                        .'Run: php artisan db:seed --class=RiderDriverDemoAccountsSeeder'
                    );
                }

                foreach (self::FLEET as $position => $spec) {
                    $sequence++;
                    $inMaintenance = $position === array_key_last(self::FLEET);

                    LogisticsCompanyVehicle::updateOrCreate(
                        [
                            'logistics_company_id' => $company->id,
                            'vehicle_code' => 'TRK-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                        ],
                        [
                            'province_hub_id' => $hub->id,
                            'plate_number' => 'VND '.str_pad((string) (1000 + $sequence), 4, '0', STR_PAD_LEFT),
                            'vehicle_type' => $spec['type'],
                            'capacity_kg' => $spec['capacity_kg'],
                            'status' => $inMaintenance ? 'maintenance' : 'available',
                            'maintenance_note' => $inMaintenance ? self::MAINTENANCE_NOTE : null,
                            'maintenance_at' => $inMaintenance ? now() : null,
                        ],
                    );
                }

                $summary[] = "{$hub->name} → ".count(self::FLEET).' vehicles';
            }

            return $summary;
        });

        foreach ($summary as $line) {
            $this->command?->line($line);
        }
        $this->command?->info('Company vehicles are seeded for the demo Province Hubs.');
    }

    /** Same host Company as the demo accounts: Vendo Express when it exists. */
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
}
