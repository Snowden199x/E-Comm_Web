<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Services\LocationCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Geoapify proxy for the mobile address pin picker.
 *
 * The web checkout calls Geoapify straight from the browser; the app goes
 * through these endpoints so the API key never ships inside the app, the
 * results are the same, and the province/city names come back already matched
 * to PSGC codes the address form uses.
 */
class BuyerGeoController extends Controller
{
    private const ENDPOINT = 'https://api.geoapify.com/v1/geocode';

    /**
     * Geoapify HTTPS client. Local PHP builds without a CA bundle use the
     * project's storage/certs/cacert.pem, like the rest of the checkout calls.
     */
    private function client()
    {
        $ca = storage_path('certs/cacert.pem');

        return Http::acceptJson()
            ->timeout(12)
            ->withOptions(is_file($ca) ? ['verify' => $ca] : []);
    }

    /** Map tiles for the pin picker: the same Geoapify raster style as the web. */
    public function config(): JsonResponse
    {
        $key = (string) config('services.geoapify.key');
        if ($key === '') {
            return response()->json(['message' => 'Address maps are not configured yet.'], 503);
        }

        return response()->json([
            'tile_url' => 'https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey='
                .rawurlencode($key),
            'attribution' => 'Powered by Geoapify | © OpenStreetMap contributors',
            'country_codes' => 'ph',
            'default_latitude' => 12.8797,
            'default_longitude' => 121.7740,
        ]);
    }

    /** Place search for the pin picker: up to five Philippine matches. */
    public function search(Request $request, LocationCatalog $locations): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:160'],
        ]);

        $key = (string) config('services.geoapify.key');
        if ($key === '') {
            return response()->json(['message' => 'Address search is not configured yet.'], 503);
        }

        try {
            $response = $this->client()->get(self::ENDPOINT.'/search', [
                'text' => $data['q'],
                'limit' => 5,
                'countrycodes' => 'ph',
                'lang' => 'en',
                'apiKey' => $key,
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Address search is unavailable right now.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'Address search is unavailable right now.'], 502);
        }

        return response()->json([
            'results' => collect($response->json('features') ?? [])
                ->map(fn (array $feature) => $this->place($feature, $locations))
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    /** Reverse geocode the pin into an address plus PSGC province/city codes. */
    public function reverse(Request $request, LocationCatalog $locations): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $key = (string) config('services.geoapify.key');
        if ($key === '') {
            return response()->json(['message' => 'Address search is not configured yet.'], 503);
        }

        try {
            $response = $this->client()->get(self::ENDPOINT.'/reverse', [
                'lat' => $data['lat'],
                'lon' => $data['lng'],
                'limit' => 1,
                'countrycodes' => 'ph',
                'lang' => 'en',
                'apiKey' => $key,
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Address lookup is unavailable right now.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'Address lookup is unavailable right now.'], 502);
        }

        $feature = ($response->json('features') ?? [])[0] ?? null;
        $place = is_array($feature) ? $this->place($feature, $locations) : null;
        if ($place === null) {
            return response()->json(
                ['message' => 'No address was found for this pin. Move it a little and try again.'],
                422
            );
        }

        return response()->json(['result' => $place]);
    }

    /**
     * One Geoapify feature as the app needs it: coordinates, the written
     * address, its parts and the PSGC codes matching the parts.
     *
     * @param  array<string, mixed>  $feature
     * @return array<string, mixed>|null
     */
    private function place(array $feature, LocationCatalog $locations): ?array
    {
        $properties = $feature['properties'] ?? null;
        if (! is_array($properties)) {
            return null;
        }

        $coordinates = $feature['geometry']['coordinates'] ?? null;
        $lat = $properties['lat'] ?? (is_array($coordinates) ? ($coordinates[1] ?? null) : null);
        $lng = $properties['lon'] ?? (is_array($coordinates) ? ($coordinates[0] ?? null) : null);
        $formatted = trim((string) ($properties['formatted'] ?? ''));
        if ($lat === null || $lng === null || $formatted === '') {
            return null;
        }

        $city = (string) ($properties['city'] ?? $properties['county'] ?? $properties['district'] ?? '');
        $barangay = (string) ($properties['suburb'] ?? $properties['neighbourhood']
            ?? $properties['quarter'] ?? $properties['district'] ?? '');
        if ($barangay !== '' && $locations->normalize($barangay) === $locations->normalize($city)) {
            $barangay = '';
        }
        $street = trim(implode(' ', array_filter([
            $properties['housenumber'] ?? null,
            $properties['street'] ?? null,
        ])));
        if ($street === '') {
            $street = (string) ($properties['address_line1'] ?? '');
        }
        $province = (string) ($properties['state'] ?? $properties['region'] ?? '');

        [$provinceCode, $cityCode] = $locations->codesForNames($province, $city);

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'formatted' => $formatted,
            'street' => $street,
            'barangay' => $barangay,
            'city' => $city,
            'city_code' => $cityCode,
            'province' => $province,
            'province_code' => $provinceCode,
            'postcode' => (string) ($properties['postcode'] ?? ''),
        ];
    }
}
