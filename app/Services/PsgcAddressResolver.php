<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PsgcAddressResolver
{
    public function __construct(private readonly LocationCatalog $locations) {}

    /**
     * Validate address names against the bundled PSGC catalog and return the
     * canonical names used by routing and account records.
     *
     * @return array{province:string, municipality:string, barangay:string}
     */
    public function resolve(array $address): array
    {
        $province = trim((string) ($address['province'] ?? ''));
        $municipality = trim((string) ($address['municipality'] ?? ''));
        $barangay = trim((string) ($address['barangay'] ?? ''));
        $codes = $this->locations->codesForNames($province, $municipality);

        if (! $codes) {
            throw ValidationException::withMessages([
                'municipality' => 'Choose a valid municipality or city within the selected province.',
            ]);
        }

        $canonical = $this->locations->address((string) $codes[0], (string) $codes[1]);
        $barangays = $this->locations->barangaysForCity((string) $codes[1]) ?? [];
        $canonicalBarangay = collect($barangays)->first(fn (string $name) =>
            $this->locations->normalize($name) === $this->locations->normalize($barangay));

        if (! $canonicalBarangay) {
            throw ValidationException::withMessages([
                'barangay' => 'Choose a valid barangay within the selected municipality or city.',
            ]);
        }

        return [
            'province' => $canonical['province'],
            'municipality' => $canonical['city'],
            'barangay' => $canonicalBarangay,
        ];
    }
}
