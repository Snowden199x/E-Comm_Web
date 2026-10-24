# Vendo Commission and Shipping Settlement Design

**Status:** Current Vendo rule and partial implementation record
**Reviewed:** 23 October 2026
**Reference:** `aisley_commission_shipping_calculations.pdf` (Aisley source snapshot stated in that PDF: `51d96947569d1603fdfb6d264ebd2a04bf866280`)

This document uses Aisley's code-grounded guide as a reference for shipping charges, Logistics/Rider earnings, frozen prices, rounding, and future settlement. Vendo has two separate money flows: **Admin's fixed 10% product commission**, calculated per eligible item, and **shipping charges paid by the Buyer**, from which Logistics/Rider service earnings are calculated and allocated by route work. Admin's 10% is not the Logistics/Rider pool and is not split with them.

## Current Vendo behavior

- Admin commission reports calculate `order_items.price × quantity` for orders whose current status is `delivered` or `completed`, then apply a fixed 10% product rate.
- At 10%, a ₱1,000 eligible item line produces ₱100 Admin product commission. This is separate from shipping charges and any Logistics/Rider earnings.
- The Admin rate editor and route have been removed; `CommissionSetting::currentRate()` returns 10.00 regardless of the legacy database row.
- Historical report totals use the fixed 10% rule; an order does not preserve its own applicable commission rate/amount.
- Checkout stores item price, Seller voucher discount, and a server-calculated shipping quote per Seller order. Buyer payable includes the fee. The quote requires one unambiguous active Station Hub for the Seller and Buyer municipalities and stores the ordered facility stops. Voucher discounts currently reduce Buyer payable but not the Admin gross-item commission base; funding/net Seller settlement needs a business decision.
- Category commission fields exist in the schema but current reports use the fixed 10% rate.
- Admin's “commission owed/earned” figures are report calculations, not payable balances. Delivered facility-routed orders write a shipping settlement ledger and verified per-stop Rider/Driver accruals, but no cash/bank payout, reversal, or reconciliation UI exists.
- Vendo uses COD. `returned` has no complete return/refund settlement workflow.

## Intended Vendo money flow

1. Buyer selects merchandise and destination.
2. Vendo charges **₱45 for the first two actual route facility stops**, plus **₱10 for each stop beyond two**, and shows each Seller order's fee and the combined checkout total.
3. Admin's fixed 10% product commission is calculated separately from the item price. It is not a shipping surcharge and is not paid out to Logistics or Rider.
4. For each completed actual facility stop, the Rider or Transport Driver who performed and verified the work can accrue **₱10**. The fee remaining after those credits belongs to the Logistics pool. If there is no eligible completed Rider/Driver task for a stop, no Rider credit is created and that amount stays with Logistics. Admin receives no shipping share.

Conceptually: `buyer total = merchandise total + quoted shipping fee - applicable discounts`.

The current quote counts ordered real facility stops (Station Hubs and Province Hubs) produced by the same resolver used by Logistics routing. The quote is per seller-specific order. A checkout with multiple sellers computes one quote for each order and sums them. Example: a three-stop order costs ₱55; up to ₱30 can accrue to Riders/Drivers and the remainder, ₱25, stays in the Logistics pool. A four-stop route costs ₱65; up to ₱40 can accrue and ₱25 remains for Logistics.

## Aisley model used as a shipping reference

The PDF describes a route quote with:

- **First mile:** pickup from Seller to origin Main Hub.
- **Linehaul:** each hub-to-hub or SubHub service leg, priced separately.
- **Last mile:** destination hub/SubHub to Buyer.
- **Destination surcharge:** applied once per Shop Order.

Each service leg contributes a base charge and a weight-based extra. Billable weight compares actual weight with volumetric weight per item, rounds the volumetric value up per unit, multiplies by quantity, and sums the units. A complete route quote is the sum of its legs plus the destination surcharge. If the route or a required tariff is incomplete, Aisley discards partial leg charges and falls back to the destination surcharge only.

Aisley separately computes its Logistics commission from the Logistics shipping pool, then allocates the remaining pool across verified legs in proportion to their quoted charges. It uses integer-centavo arithmetic and assigns rounding remainder deterministically so allocations equal the pool. Its Seller commission is a distinct calculation. These rates and policies belong to Aisley; they are not automatically Vendo settings.

## Implemented accrual rules and limits

- Checkout requires active, unambiguous Seller-origin and Buyer-destination Station Hubs. If the route is missing or ambiguous, checkout reports that shipping is unavailable rather than selecting an arbitrary company or charging a guessed fee.
- The amount, stop count, and route facility snapshot are stored on each order. The authoritative fee is recomputed inside the order-creation transaction; a changed or tampered quote is rejected.
- On a successful delivered scan, one settlement is created for the order. The actual ordered route must match the quote snapshot and all stops must show receipt/sorting, or the settlement enters `review` and creates no Rider credits.
- The first stop can be credited to the completed pickup RiderTask; the last stop to the completed delivery RiderTask; intermediate stops to the completed manifest Transport Driver whose trip delivered the parcel to that facility. A single-stop route prefers its completed delivery task, then pickup task. At most one ₱10 credit is made per stop.
- `order_shipping_settlements` stores the full fee, count, accrued Rider total, Logistics remainder, and `accrued`/`review` status. `order_shipping_earning_lines` is idempotent per settlement and route position. Rider and Transport Driver API totals include only their own accrued rows for delivered orders.
- Company Reports summarizes shipping fees on settled orders, earnings credited to stops at that Company’s facilities, and the Logistics remainder for single-company routes. A remainder on a route touching multiple companies is surfaced as unallocated instead of being duplicated or assigned arbitrarily. These report values are accrual/pool records, not payouts.
- Accrual is not disbursement. Failed deliveries, cancellation, returns, settlement review, payout approval/execution, and reversals have no end-to-end financial workflow. On a cross-company route, the remainder is recorded as a Logistics pool amount; the system does not divide it between companies.
- The current model does not charge by weight/dimensions. There is no claim that a real-world route tariff or a payment has been verified.

Do not assume equal splits, use the Admin 10% rate for shipping, or treat every virtual SH waypoint as a physical payable facility without an explicit rule.

## Recommended implementation principles

1. Keep Admin's product commission policy at a fixed 10%; the Admin rate editor is removed in the current code.
2. Calculate shipping separately from item prices, using explicit route legs and tariffs.
3. Freeze order item prices, shipping quote, stop count, and route inputs at checkout so future coverage changes do not rewrite old orders.
4. Use integer centavos or fixed-precision decimals and one documented rounding rule. Aisley uses integer centavos and half-up rounding.
5. Create a settlement ledger with idempotent recognition, per-beneficiary allocations, payouts, adjustments/reversals, and reconciliation. Reports alone must not be presented as money owed.
6. Allocate only against a leg with matching authorized assignment and custody evidence. Missing or contradictory evidence should hold that allocation for review.

## Code references

- `app/Models/Finance/CommissionSetting.php` — fixed 10% rate returned by commission calculations.
- `app/Http/Controllers/Admin/CommissionController.php` — Seller/month commission aggregates.
- `app/Http/Controllers/Admin/ReportController.php` — report commission calculations.
- `app/Http/Controllers/Buyer/CheckoutController.php` — checkout quote and validated shipping fee snapshots.
- `app/Services/CheckoutShippingQuoteService.php`, `app/Services/LogisticsFacilityRouteResolver.php`, `app/Services/ShippingEarningsSettlementService.php` — route quote and delivered-order accrual rules.
- `app/Http/Controllers/Api/RiderEarningsController.php` — Rider and Transport Driver accrued earnings API.
- `database/migrations/2026_09_08_140641_create_commission_settings_table.php` — global commission-rate default.
- `database/migrations/2026_09_08_141948_add_commission_rate_to_categories_table.php` — category rate column, not currently used by commission calculations.
