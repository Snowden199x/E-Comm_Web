# Commission and Settlement

**Status:** Partial: fixed Admin commission reports and route-based shipping quote/earnings ledger are implemented; migration and owner verification pending
**Reviewed:** 23 October 2026

## Current behavior

The Admin product commission is fixed at 10% per eligible item line. It is separate from future Buyer shipping charges and Logistics/Rider earnings calculated from route service legs. `CommissionSetting::currentRate()` returns the constant 10.00; the Admin edit route and UI are removed. The old database setting remains unused by calculations.

Current code calculates and reports Admin commission from order-item price × quantity for orders in `delivered` or `completed` status. The Admin commission remains a fixed 10% of eligible merchandise value; Admin receives no share of shipping. Checkout now quotes shipping from the resolved real facility route, stores the fee, hub-stop count, and route snapshot on each seller-specific order, and includes shipping in Buyer payable. Category commission fields exist in the schema but calculations use the fixed rate. There is no per-order Admin commission snapshot.

Shipping rule: ₱45 minimum covers the first two real facility stops; every stop beyond two adds ₱10. The route's ordered Station Hub and Province Hub facilities are the countable stops. Each completed stop can accrue ₱10 to the Rider or Transport Driver who performed the verified work there. A stop without an eligible completed Rider/Driver assignment contributes no Rider credit, and that amount remains in the Logistics remainder. Vendo Admin never shares this shipping pool. Example: three route stops charge ₱55; up to ₱30 can accrue to Riders, leaving at least ₱25 to Logistics.

Delivered facility-routed orders create one idempotent shipping settlement row. Verified completed pickup/delivery tasks can earn the first/last stop; a completed manifest trip can earn an intermediate stop. Rider credits are ₱10 each and the remainder is stored for Logistics. Route/snapshot mismatches are marked for review and generate no Rider credits. The mobile API returns accrued amounts only; it does not execute payouts. There is no payout, reversal, or reconciliation UI. `returned` does not currently have a complete return/refund settlement flow. The Logistics remainder is stored per order but is not yet divided among companies on cross-company routes.

## Gaps and acceptance direction

No immutable order-level Admin commission snapshot, Seller net payout accounting, payout execution, reversal, or company-level cross-company Logistics distribution exists. Aisley's per-leg shipping model remains reference material only; Vendo's current fee is the fixed minimum-plus-extra-stops rule described above. See [Vendo Commission and Settlement Design](../../../design/commission-calculation-and-settlement.md).

## Source evidence

`app/Models/Finance/CommissionSetting.php`, `app/Http/Controllers/Admin/CommissionController.php`, `app/Http/Controllers/Admin/ReportController.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
