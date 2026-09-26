<?php

namespace App\Services;

use Illuminate\Support\Str;

class LocationCatalog
{
    public function all(): array
    {
        return json_decode(file_get_contents(resource_path('data/psgc-locations.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function address(string $provinceCode, string $cityCode): ?array
    {
        $province = $this->all()[$provinceCode] ?? null;
        if (! $province || ! isset($province['cities'][$cityCode])) {
            return null;
        }

        return ['province' => $province['name'], 'city' => $province['cities'][$cityCode]];
    }

    public function codesForNames(?string $province, ?string $city): ?array
    {
        foreach ($this->all() as $provinceCode => $entry) {
            if ($this->normalize($entry['name']) !== $this->normalize($province)) {
                continue;
            }
            foreach ($entry['cities'] as $cityCode => $cityName) {
                if ($this->normalize($cityName) === $this->normalize($city)) {
                    return [$provinceCode, $cityCode];
                }
            }
        }

        return null;
    }

    public function normalize(?string $name): string
    {
        $name = Str::lower(Str::ascii(trim((string) $name)));
        $name = preg_replace('/^(city of |municipality of )/', '', $name);
        $name = preg_replace('/ city$/', '', $name);
        if (in_array($name, ['ncr', 'national capital region'], true)) {
            $name = 'metro manila';
        }
        return preg_replace('/[^a-z0-9]+/', '', $name);
    }
}
