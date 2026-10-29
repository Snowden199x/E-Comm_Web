<?php

namespace App\Services;

use App\Models\Profiles\LogisticsFacility;
use Illuminate\Validation\ValidationException;

class CheckoutShippingQuoteService
{
    public const MINIMUM_FEE_CENTS = 4500;
    public const INCLUDED_HUBS = 2;
    public const ADDITIONAL_HUB_FEE_CENTS = 1000;

    public function __construct(
        private LogisticsCenterMatcher $centers,
        private LogisticsFacilityRouteResolver $routes,
    ) {}

    public static function feeCentsFor(int $hubCount): int
    {
        return self::MINIMUM_FEE_CENTS
            + max(0, $hubCount - self::INCLUDED_HUBS) * self::ADDITIONAL_HUB_FEE_CENTS;
    }

    /**
     * @param  int|null  $companyId  The Logistics Company the Buyer chose. When given, both stations
     *                               must belong to that Company; otherwise the address match is used.
     */
    public function quote(?string $sellerProvince, ?string $sellerCity, string $buyerProvince, string $buyerCity, ?int $companyId = null): array
    {
        $station = fn (string $province, string $city) => $companyId
            ? $this->centers->stationForCompanyAt($province, $city, $companyId)
            : $this->centers->stationForAddress($province, $city);
        $origin = filled($sellerProvince) && filled($sellerCity)
            ? $station($sellerProvince, $sellerCity) : null;
        $destination = $station($buyerProvince, $buyerCity);
        if (! $origin || ! $destination) {
            throw ValidationException::withMessages([
                'shipping_city_code' => 'Shipping is not available for this address yet. Logistics must configure one active Station Hub for both the seller and delivery municipality.',
            ]);
        }

        $route = $this->routes->between($origin, $destination);
        if ($route === []) {
            throw ValidationException::withMessages([
                'shipping_city_code' => 'A complete active Logistics route is not available for this address yet.',
            ]);
        }

        $hubCount = count($route);
        $feeCents = self::feeCentsFor($hubCount);

        return [
            'shipping_fee' => number_format($feeCents / 100, 2, '.', ''),
            'hub_count' => $hubCount,
            // The Company of the destination Station Hub; its Riders make the delivery, so its rider pay applies.
            'delivery_company_id' => $destination->logistics_company_id,
            'route' => array_map(fn (LogisticsFacility $facility) => [
                'facility_id' => $facility->id,
                'name' => $facility->name,
                'kind' => $facility->kind,
            ], $route),
        ];
    }
}
