<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateLogisticsDemoAccountsPdf extends Command
{
    protected $signature = 'logistics:demo-accounts-pdf {path=output/pdf/vendo-ph-logistics-demo-accounts.pdf}';
    protected $description = 'Generate the local Philippines Logistics demo account credential PDF';

    public function handle(): int
    {
        abort_unless(app()->environment('local'), 403, 'Demo credential exports are local-development only.');
        ini_set('memory_limit', '512M');
        $catalog = json_decode(file_get_contents(resource_path('data/psgc-locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $accounts = collect();
        foreach ($catalog as $province) {
            $provinceName = $province['name'];
            $provinceSlug = Str::slug(Str::ascii($provinceName));
            $accounts->push(['province' => $provinceName, 'account_type' => 'Company', 'email' => "logistics.{$provinceSlug}@vendo.com", 'password' => \Database\Seeders\PhilippinesLogisticsCompanySeeder::PASSWORD]);
            $accounts->push(['province' => $provinceName, 'account_type' => 'Province Hub Manager', 'email' => "province.{$provinceSlug}@vendo.com", 'password' => \Database\Seeders\PhilippinesLogisticsCompanySeeder::PASSWORD]);
            foreach ($province['cities'] as $municipalityName) {
                $stationSlug = Str::slug(Str::ascii($municipalityName));
                $accounts->push(['province' => $provinceName, 'account_type' => "Station Hub Manager - {$municipalityName}", 'email' => "station.{$provinceSlug}.{$stationSlug}@vendo.com", 'password' => \Database\Seeders\PhilippinesLogisticsCompanySeeder::PASSWORD]);
            }
        }
        $accounts = $accounts->sortBy(fn (array $account) => $account['province'].' '.$account['account_type'], SORT_NATURAL | SORT_FLAG_CASE)->values();

        $path = $this->argument('path');
        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }

        Pdf::loadView('admin.logistics.demo-accounts-pdf', ['accounts' => $accounts])
            ->setPaper('a4', 'landscape')->save($path);
        @chmod($path, 0600);
        $this->info('Created '.$accounts->count().' logistics credentials: '.$path);

        return self::SUCCESS;
    }
}
