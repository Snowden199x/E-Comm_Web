<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Database\Seeders\NationalLogisticsCompaniesSeeder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

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
        foreach (NationalLogisticsCompaniesSeeder::COMPANIES as $companyKey => $companyName) {
            $accounts->push([
                'company' => $companyName,
                'province' => 'Nationwide',
                'facility' => 'Company account',
                'account_type' => 'Company Admin',
                'email' => NationalLogisticsCompaniesSeeder::companyEmail($companyKey),
                'password' => NationalLogisticsCompaniesSeeder::PASSWORD,
            ]);

            foreach ($catalog as $provinceCode => $province) {
                $provinceCode = (string) $provinceCode;
                $provinceName = $province['name'];
                $accounts->push([
                    'company' => $companyName,
                    'province' => $provinceName,
                    'facility' => "{$provinceName} Province Hub",
                    'account_type' => 'Province Hub Manager',
                    'email' => NationalLogisticsCompaniesSeeder::provinceManagerEmail($companyKey, $provinceCode),
                    'password' => NationalLogisticsCompaniesSeeder::PASSWORD,
                ]);

                foreach ($province['cities'] as $municipalityCode => $municipalityName) {
                    $accounts->push([
                        'company' => $companyName,
                        'province' => $provinceName,
                        'facility' => "{$municipalityName} Station Hub",
                        'account_type' => 'Station Hub Manager',
                        'email' => NationalLogisticsCompaniesSeeder::stationManagerEmail(
                            $companyKey,
                            $provinceCode,
                            (string) $municipalityCode,
                        ),
                        'password' => NationalLogisticsCompaniesSeeder::PASSWORD,
                    ]);
                }
            }
        }

        $path = $this->argument('path');
        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }

        $pdfunite = (new ExecutableFinder())->find('pdfunite');
        if (! $pdfunite) {
            $this->error('The pdfunite utility is required to combine the three company account sections. Install Poppler tools and retry.');

            return self::FAILURE;
        }

        $temporaryDirectory = sys_get_temp_dir().'/vendo-logistics-pdf-'.bin2hex(random_bytes(8));
        if (! mkdir($temporaryDirectory, 0700, true) && ! is_dir($temporaryDirectory)) {
            $this->error('Could not create a temporary directory for PDF generation.');

            return self::FAILURE;
        }

        $companyPdfs = [];
        try {
            $partIndex = 0;
            foreach (array_keys(NationalLogisticsCompaniesSeeder::COMPANIES) as $companyKey) {
                $companyName = NationalLogisticsCompaniesSeeder::COMPANIES[$companyKey];
                $companyAccounts = $accounts->where('company', $companyName)->values();
                foreach ($companyAccounts->chunk(250) as $accountChunk) {
                    $partPath = $temporaryDirectory.'/section-'.str_pad((string) ++$partIndex, 3, '0', STR_PAD_LEFT).'.pdf';

                    Pdf::loadView('admin.logistics.demo-accounts-pdf', [
                        'accounts' => $accountChunk->values(),
                        'companySection' => $companyName,
                    ])->setPaper('a4', 'landscape')->save($partPath);
                    $companyPdfs[] = $partPath;
                    gc_collect_cycles();
                }
            }

            (new Process([$pdfunite, ...$companyPdfs, $path]))->mustRun();
        } finally {
            foreach (glob($temporaryDirectory.'/*') ?: [] as $temporaryFile) {
                @unlink($temporaryFile);
            }
            @rmdir($temporaryDirectory);
        }

        @chmod($path, 0600);
        $this->info('Created '.$accounts->count().' logistics credentials: '.$path);

        return self::SUCCESS;
    }
}
