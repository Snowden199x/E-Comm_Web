<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Services\LocationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/** PSGC province/city picker data for the mobile address form and checkout. */
class BuyerLocationController extends Controller
{
    public function index(LocationCatalog $locations): JsonResponse
    {
        $provinces = collect($locations->all())
            ->map(fn (array $province, string $code) => [
                'code' => $code,
                'name' => $province['name'],
                'cities' => collect($province['cities'])
                    ->map(fn (string $name, string $cityCode) => ['code' => (string) $cityCode, 'name' => $name])
                    ->values()->all(),
            ])->values()->all();

        return response()->json(['provinces' => $provinces]);
    }

    public function barangays(string $cityCode, LocationCatalog $locations): JsonResponse
    {
        $barangays = $locations->barangaysForCity($cityCode);
        if ($barangays === null) {
            return response()->json(['message' => 'That city or municipality was not found.'], 404);
        }

        return response()->json([
            'city_code' => $cityCode,
            'barangays' => collect($barangays)
                ->map(fn (string $name, string $code) => ['code' => (string) $code, 'name' => $name])
                ->values()->all(),
        ]);
    }

    /** Resolve free-text names saved on the buyer profile to PSGC codes for checkout. */
    public static function codesForNames(LocationCatalog $locations, ?string $province, ?string $city): array
    {
        $provinceCode = $province ? $locations->provinceCodeForName($province) : null;
        $cityCode = null;
        if ($provinceCode && $city) {
            foreach ($locations->all()[$provinceCode]['cities'] as $code => $name) {
                if (Str::lower($name) === Str::lower($city)) {
                    $cityCode = (string) $code;
                    break;
                }
            }
        }

        return [$provinceCode, $cityCode];
    }
}
