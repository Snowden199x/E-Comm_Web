# Commission and Settlement

**Status:** Partial: report calculations and global rate editing exist; no settlement ledger
**Reviewed:** 7 October 2026

## Current behavior

The owner's intended Admin product commission is fixed at 10% per eligible item line. It is separate from future Buyer shipping charges and Logistics/Rider earnings calculated from route service legs. `CommissionSetting::currentRate()` and its migration default to 10.00, but Admin can currently edit the global stored rate, so code does not enforce the fixed-rate policy.

Current code calculates and reports Admin commission from order-item price × quantity for orders in `delivered` or `completed` status. It does not calculate shipping or credit Logistics/Riders. Checkout stores item price snapshots, sets `shipping_fee` to zero, and does not implement vouchers or a shipping quote. Category commission fields exist in the schema but calculations use the global rate. Historical reports therefore change if the global rate changes.

Initial planning assumption for a future Buyer shipping fee is ₱30–₱60 total per seller-specific order, capped at ₱60 per order, regardless of how many hubs/SHs are on the route. This is not configured or implemented; validate the cap against actual route, weight, and vehicle tariffs before treating it as a production rate.

The displayed commission is a report calculation, not an actual payable balance. There is no settlement ledger, payout execution, reversal, or reconciliation workflow. `returned` does not currently have a complete return/refund settlement flow.

## Gaps and acceptance direction

No immutable order-level Admin rate snapshot, shipping quote, Logistics/Rider beneficiary allocation, or payout ledger exists. Aisley's per-leg shipping model is a reference for a future Vendo shipping calculation, separate from the fixed Admin 10% product commission. See [Vendo Commission and Settlement Design](../../../design/commission-calculation-and-settlement.md).

## Source evidence

`app/Models/Finance/CommissionSetting.php`, `app/Http/Controllers/Admin/CommissionController.php`, `app/Http/Controllers/Admin/ReportController.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
