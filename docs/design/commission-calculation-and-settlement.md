# Vendo Commission and Shipping Settlement Design

**Status:** Design reference; current behavior is described separately below  
**Reviewed:** 7 October 2026  
**Reference:** `aisley_commission_shipping_calculations.pdf` (Aisley source snapshot stated in that PDF: `51d96947569d1603fdfb6d264ebd2a04bf866280`)

This document uses Aisley's code-grounded guide as a reference for shipping charges, Logistics/Rider earnings, frozen prices, rounding, and future settlement. Vendo has two separate money flows: **Admin's fixed 10% product commission**, calculated per eligible item, and **shipping charges paid by the Buyer**, from which Logistics/Rider service earnings are calculated and allocated by route work. Admin's 10% is not the Logistics/Rider pool and is not split with them.

## Current Vendo behavior

- Admin commission reports calculate `order_items.price × quantity` for orders whose current status is `delivered` or `completed`, then apply the configured global rate. The intended fixed Admin product commission is 10% per eligible item line.
- At 10%, a ₱1,000 eligible item line produces ₱100 Admin product commission. This is separate from shipping charges and any Logistics/Rider earnings.
- The Admin rate screen technically allows editing the global rate, even though the business rule is fixed at 10%. The current code therefore does not enforce the fixed-rate policy.
- Historical report totals use the **current** configured rate; an order does not preserve its applicable commission rate/amount.
- Checkout stores item price snapshots but currently sets `orders.shipping_fee = 0`. There is no shipping quote, route tariff, or Logistics/Rider shipping allocation.
- Category commission fields exist in the schema but current reports use the global rate.
- Admin's “commission owed/earned” figures are report calculations, not payable balances. There is no commission or shipping settlement ledger, allocation, payout, reversal, or reconciliation workflow.
- Vendo uses COD. `returned` has no complete return/refund settlement workflow.

## Intended Vendo money flow

1. Buyer selects merchandise and destination.
2. Vendo calculates shipping from the services needed for the route, then shows the Buyer the merchandise total plus the shipping fee (less any applicable future discounts).
3. Admin's fixed 10% product commission is calculated separately from the item price. It is not a shipping surcharge and is not paid out to Logistics or Rider.
4. The shipping amount funds the applicable route services. Logistics and Rider earnings are calculated separately from the shipping quote and allocated to the organizations/people who perform and verify those legs.

Conceptually: `buyer total = merchandise total + quoted shipping fee - applicable discounts`.

The exact Vendo shipping tariff, any separate Logistics commission on shipping, and the amount/rule paid to each Rider or Logistics Center still need to be defined. Do not apply Admin's 10% product rate to shipping unless the owner explicitly chooses a separate shipping rate.

### Initial shipping-fee planning assumption

For initial Vendo planning, use **₱30–₱60 total shipping fee per seller-specific order**, with **₱60 as the maximum**. This is the total fee for the order's complete route, not a fee charged separately for every hub or SH. A checkout containing multiple sellers should show each seller order's fee and add those fees into the checkout total. The route-leg rates should determine how the fee is allocated among services; a three-hub route does not automatically mean three times the fee. This is a provisional planning assumption, not a tariff currently configured or implemented in Vendo.

## Aisley model used as a shipping reference

The PDF describes a route quote with:

- **First mile:** pickup from Seller to origin Main Hub.
- **Linehaul:** each hub-to-hub or SubHub service leg, priced separately.
- **Last mile:** destination hub/SubHub to Buyer.
- **Destination surcharge:** applied once per Shop Order.

Each service leg contributes a base charge and a weight-based extra. Billable weight compares actual weight with volumetric weight per item, rounds the volumetric value up per unit, multiplies by quantity, and sums the units. A complete route quote is the sum of its legs plus the destination surcharge. If the route or a required tariff is incomplete, Aisley discards partial leg charges and falls back to the destination surcharge only.

Aisley separately computes its Logistics commission from the Logistics shipping pool, then allocates the remaining pool across verified legs in proportion to their quoted charges. It uses integer-centavo arithmetic and assigns rounding remainder deterministically so allocations equal the pool. Its Seller commission is a distinct calculation. These rates and policies belong to Aisley; they are not automatically Vendo settings.

## Vendo decisions needed before implementation

- Which shipping services apply for each Vendo order path: Seller pickup, Main Hub sort, SH handoffs, truck linehaul, destination hub sort, and final delivery.
- How weight and package dimensions affect each leg's tariff; which service categories and destination surcharges apply.
- Whether the provisional ₱30–₱60 per seller-order range and ₱60 total cap remain viable for all routes, weights, and vehicle types.
- Whether Vendo deducts a separate Logistics commission from the shipping amount, and its rate.
- How shipping earnings are split among Main Hubs and Riders, including cases where pickup and delivery use different Riders, a truck covers linehaul, or a parcel is reassigned.
- Which authenticated scans/hand-off records make each leg payable. A planned route by itself must never count as proof of arrival or completed service.
- Whether Buyer or Rider delivery status triggers recognition, and the rules for failed delivery, cancellation, return, and refund.
- How discounts are funded and whether a discount reduces merchandise, shipping, or both.

Do not assume equal splits, use the Admin 10% rate for shipping, or treat every virtual SH waypoint as a physical payable facility without an explicit rule.

## Recommended implementation principles

1. Keep Admin's product commission policy at a fixed 10%. Remove or constrain the current Admin rate editor if the fixed-rate rule is to be enforced in code.
2. Calculate shipping separately from item prices, using explicit route legs and tariffs.
3. Freeze order item prices, Admin rate, shipping quote, tariff versions, and allocation inputs at checkout so future rate changes do not rewrite old orders.
4. Use integer centavos or fixed-precision decimals and one documented rounding rule. Aisley uses integer centavos and half-up rounding.
5. Create a settlement ledger with idempotent recognition, per-beneficiary allocations, payouts, adjustments/reversals, and reconciliation. Reports alone must not be presented as money owed.
6. Allocate only against a leg with matching authorized assignment and custody evidence. Missing or contradictory evidence should hold that allocation for review.

## Code references

- `app/Models/Finance/CommissionSetting.php` — global configured-rate lookup with 10% fallback.
- `app/Http/Controllers/Admin/CommissionController.php` — Admin rate editing and Seller/month aggregates.
- `app/Http/Controllers/Admin/ReportController.php` — report commission calculations.
- `app/Http/Controllers/Buyer/CheckoutController.php` — item price snapshots and zero shipping fee.
- `database/migrations/2026_09_08_140641_create_commission_settings_table.php` — global commission-rate default.
- `database/migrations/2026_09_08_141948_add_commission_rate_to_categories_table.php` — category rate column, not currently used by commission calculations.
