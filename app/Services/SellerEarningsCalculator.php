<?php

namespace App\Services;

/**
 * What a Seller earns from one order. Vendo's commission is a percentage of the gross item price.
 * Seller vouchers are funded by the Seller, so the voucher discount comes off the Seller's earnings
 * (the Buyer, and therefore Vendo, collected that much less cash). All amounts are integer centavos.
 */
class SellerEarningsCalculator
{
    public function __construct(private DeliveryFeeDistributionCalculator $percent) {}

    /** @return array{commission_cents:int, net_cents:int} */
    public function earnings(int $itemsCents, int $voucherCents, float $commissionRatePercent): array
    {
        $commissionCents = $this->percent->percentOf($itemsCents, $commissionRatePercent);

        return [
            'commission_cents' => $commissionCents,
            'net_cents' => max(0, $itemsCents - max(0, $voucherCents) - $commissionCents),
        ];
    }
}
