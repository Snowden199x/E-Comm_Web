<?php

namespace App\Services;

use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsFacility;

/**
 * Lists the Logistics Companies a Buyer may choose for one Seller's parcel.
 *
 * A Company is offered only when its own active Station Hub network reaches BOTH the Seller's
 * municipality and the delivery municipality. A Company that serves just one end is left out,
 * so every visible choice can actually pick the parcel up and deliver it.
 */
class CheckoutLogisticsOptionsService
{
    public function __construct(
        private LogisticsCenterMatcher $centers,
        private LogisticsFacilityRouteResolver $routes,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function options(?string $sellerProvince, ?string $sellerCity, string $buyerProvince, string $buyerCity): array
    {
        if (! filled($sellerProvince) || ! filled($sellerCity)) {
            return [];
        }

        $companies = LogisticsCompany::query()
            ->where('status', 'approved')
            ->whereHas('logisticsCenter.user', fn ($user) => $user->where('role', 'logistics_center')
                ->where('status', 'approved')->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->orderBy('business_name')
            ->get();

        $options = [];
        foreach ($companies as $company) {
            $origin = $this->centers->stationForCompanyAt($sellerProvince, $sellerCity, $company->id);
            $destination = $this->centers->stationForCompanyAt($buyerProvince, $buyerCity, $company->id);
            if (! $origin || ! $destination) {
                continue;
            }

            $route = $this->routes->between($origin, $destination);
            if ($route === []) {
                continue;
            }

            $options[] = [
                'company_id' => $company->id,
                'company_name' => $company->business_name,
                'origin_station_id' => $origin->id,
                'destination_station_id' => $destination->id,
                'hub_count' => count($route),
                'shipping_fee' => number_format(CheckoutShippingQuoteService::feeCentsFor(count($route)) / 100, 2, '.', ''),
                'route' => array_map(fn (LogisticsFacility $facility) => [
                    'facility_id' => $facility->id,
                    'name' => $facility->name,
                    'kind' => $facility->kind,
                    'municipality' => $facility->municipality,
                ], $route),
            ];
        }

        usort($options, fn ($a, $b) => [(float) $a['shipping_fee'], $a['company_name']] <=> [(float) $b['shipping_fee'], $b['company_name']]);

        foreach ($options as $index => &$option) {
            $option['is_cheapest'] = $index === 0;
        }
        unset($option);

        return $options;
    }
}
