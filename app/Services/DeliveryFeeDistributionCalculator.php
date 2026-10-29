<?php

namespace App\Services;

/**
 * Splits a delivery fee between the Rider, Vendo and the Logistics Company.
 *
 * Rider pay is a fixed amount per delivered parcel. Vendo takes a percentage of what is left after
 * the rider's pay. The Logistics Company receives the remainder, so the three parts always add up
 * to the delivery fee exactly. All math is in integer centavos with half-up rounding.
 */
class DeliveryFeeDistributionCalculator
{
    /** @return array{rider_cents:int, remaining_cents:int, vendo_cents:int, logistics_cents:int} */
    public function split(int $feeCents, int $riderPayCents, float $vendoRatePercent): array
    {
        $feeCents = max(0, $feeCents);
        $riderCents = min(max(0, $riderPayCents), $feeCents);
        $remainingCents = $feeCents - $riderCents;
        $vendoCents = $this->percentOf($remainingCents, $vendoRatePercent);

        return [
            'rider_cents' => $riderCents,
            'remaining_cents' => $remainingCents,
            'vendo_cents' => $vendoCents,
            'logistics_cents' => $remainingCents - $vendoCents,
        ];
    }

    /** A percentage (0-100, two decimals) of an amount in centavos, rounded half up. */
    public function percentOf(int $cents, float $percent): int
    {
        $basisPoints = max(0, min(10000, (int) round($percent * 100)));

        return intdiv(max(0, $cents) * $basisPoints + 5000, 10000);
    }
}
