# Commission and Settlement

**Status:** Partial: fixed Admin commission reports and route-based shipping quote/earnings ledger are implemented; migration and owner verification pending
**Reviewed:** 28 October 2026

## Current behavior

The Admin product commission is fixed at 10% per eligible item line. It is separate from future Buyer shipping charges and Logistics/Rider earnings calculated from route service legs. `CommissionSetting::currentRate()` returns the constant 10.00; the Admin edit route and UI are removed. The old database setting remains unused by calculations.

Current code calculates and reports Admin commission from order-item price × quantity for orders in `delivered` or `completed` status. The Admin commission remains a fixed 10% of eligible merchandise value; Admin receives no share of shipping. Checkout now quotes shipping from the resolved real facility route, stores the fee, hub-stop count, and route snapshot on each seller-specific order, and includes shipping in Buyer payable. Category commission fields exist in the schema but calculations use the fixed rate. There is no per-order Admin commission snapshot.

Shipping rule: ₱45 minimum covers the first two real facility stops; every stop beyond two adds ₱10. The route's ordered Station Hub and Province Hub facilities are the countable stops. Each completed stop can accrue ₱10 to the Rider or Transport Driver who performed the verified work there. A stop without an eligible completed Rider/Driver assignment contributes no Rider credit, and that amount remains in the Logistics remainder. Vendo Admin never shares this shipping pool. Example: three route stops charge ₱55; up to ₱30 can accrue to Riders, leaving at least ₱25 to Logistics.

Delivered facility-routed orders create one idempotent shipping settlement row. Verified completed pickup/delivery tasks can earn the first/last stop; a completed manifest trip can earn an intermediate stop. Rider credits are ₱10 each and the remainder is stored for Logistics. Route/snapshot mismatches are marked for review and generate no Rider credits. The mobile API returns accrued amounts only; it does not execute payouts. There is no payout, reversal, or reconciliation UI. `returned` does not currently have a complete return/refund settlement flow. The Logistics remainder is stored per order but is not yet divided among companies on cross-company routes.

## Per-parcel delivery fee split for new orders (28 October 2026)

Checkout now saves, on each new seller-specific order: `cod_amount` (items minus voucher plus delivery fee, the cash the Rider must collect), `money_status = not_collected`, and the rates in force at that moment: `rider_pay_rate` (the destination Station Hub Company's `rider_pay_per_parcel`, default ₱10), `vendo_delivery_commission_rate` and `seller_commission_rate` (from `delivery_fee_settings`, defaults 0% and 10%). Later changes to those settings never alter an existing order.

When such an order is scanned delivered, `ShippingEarningsSettlementService` splits the delivery fee with `DeliveryFeeDistributionCalculator`: the delivery Rider (the completed `delivery` task at the last route stop) receives the fixed `rider_pay_rate` once per parcel; Vendo receives `vendo_delivery_commission_rate` percent of the balance after that payment (half-up to the centavo); the Logistics Company keeps the rest, so the parts always add up to the fee. Example: fee ₱100, rider ₱10, Vendo 20% gives ₱10 / ₱18 / ₱72. If no completed delivery task exists, no rider payment is made and the fee is split with a rider share of zero. The result is stored in `order_shipping_settlements.rider_total`, `vendo_commission` and `logistics_remainder`, with one `order_shipping_earning_lines` row for the Rider. Route/snapshot mismatches still go to `review` with no credits. Pickup riders and Transport Drivers receive no per-parcel credit under this rule; their pay is a salary outside the system.

Orders created before this change (null `rider_pay_rate`) keep the earlier per-stop ₱10 rule so existing settlements and Rider earnings are unchanged. The Admin 10% merchandise commission reports still use the fixed rate; `seller_commission_rate` is saved but not yet read. Cash collection at delivery is now recorded (see the [scan API spec](../../courier/scan-api/spec.md): `cash_collected` must equal `cod_amount`, writes `order_cod_collections` and sets `money_status = collected_by_rider`). Station Hub receipt and remittances up the chain, Admin rate editing, Vendo's receipt confirmation and the Seller earnings page are now built (see the [Admin delivery fees spec](../../admin/delivery-fees-and-remittances/spec.md) and [Seller earnings spec](../../seller/earnings/spec.md)); the Admin Commission and Reports pages use each order's saved seller rate.

## Gaps and acceptance direction

No immutable order-level Admin commission snapshot, Seller net payout accounting, payout execution, reversal, or company-level cross-company Logistics distribution exists. Aisley's per-leg shipping model remains reference material only; Vendo's current fee is the fixed minimum-plus-extra-stops rule described above. See [Vendo Commission and Settlement Design](../../../design/commission-calculation-and-settlement.md).

## Source evidence

`app/Models/Finance/CommissionSetting.php`, `app/Http/Controllers/Admin/CommissionController.php`, `app/Http/Controllers/Admin/ReportController.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
