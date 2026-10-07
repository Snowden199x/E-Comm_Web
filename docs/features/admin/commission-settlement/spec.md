# Commission Settings

**Status:** Partial: global rate editing and report calculations exist; no settlement ledger
**Reviewed:** 7 October 2026

## Current behavior

Admin can inspect a global commission rate, Seller/month merchandise totals, and calculated commission, and update the global rate. Calculations include item price × quantity for orders in `delivered` or `completed` status and apply the current configured rate. The model default is 10%, matching the owner's fixed Admin product commission policy, but the rate-edit action means the current UI does not enforce that fixed rate. Historical report totals use the current rate, not a rate frozen on each order. The displayed amount is not a payable balance and is separate from future Logistics/Rider shipping earnings.

## Gaps and acceptance direction

The fixed 10% rate is not enforced by the edit UI and there is no immutable order commission snapshot. Shipping quotation, route-leg tariffs, Logistics/Rider beneficiary allocation, payout/settlement ledger, reconciliation, reversals, and disbursement workflow are not implemented. See the [commission calculation and settlement design](../../../design/commission-calculation-and-settlement.md) for the Aisley-based shipping reference and Vendo-specific boundaries.

## Source evidence

`app/Http/Controllers/Admin/CommissionController.php`, `app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
