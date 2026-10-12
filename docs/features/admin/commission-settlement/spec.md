# Commission Settings

**Status:** Partial: fixed 10% report calculations exist; no settlement ledger
**Reviewed:** 12 October 2026

## Current behavior

Admin can inspect Seller/month merchandise totals and calculated commission. `CommissionSetting::currentRate()` always returns 10%; the Admin rate editor and POST endpoint were removed. Calculations include item price × quantity for orders in `delivered` or `completed` status and apply 10% to that gross item value. Seller vouchers reduce the Buyer's payable total, but currently do not reduce the Admin commission base. The displayed amount is a report calculation, not a payable balance, and is separate from future Logistics/Rider shipping earnings.

## Gaps and acceptance direction

There is no immutable order commission snapshot. Shipping quotation, route-leg tariffs, Logistics/Rider beneficiary allocation, payout/settlement ledger, reconciliation, reversals, and disbursement workflow are not implemented. The policy for funding Seller vouchers and settling the Seller's net proceeds also needs a decision. See the [commission calculation and settlement design](../../../design/commission-calculation-and-settlement.md) for the Aisley-based shipping reference and Vendo-specific boundaries.

## Source evidence

`app/Http/Controllers/Admin/CommissionController.php`, `app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
