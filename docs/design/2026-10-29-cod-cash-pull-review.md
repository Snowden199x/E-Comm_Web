# COD cash flow pull review — 29 October 2026

## Pull reviewed

The update on `dev` is PR #83 (`331893b`, containing `d4088f7` and the `dev` merge `2f3edd5` from PR #81), pulled with `git pull --rebase origin main` on 29 October 2026 and fast-forwarded onto the last local commit `bdaf915`. The reviewed diff touches 69 files. This is a code and documentation review: no automated test, page render, database query, or browser check was run.

## What arrived

### Database (3 additive migrations, owner must run)

- `2026_10_28_100000_add_cod_and_delivery_fee_settings.php`: `delivery_fee_settings` (one row inserted with defaults), `logistics_companies.rider_pay_per_parcel`, `orders.cod_amount`/`money_status`/`rider_pay_rate`/`vendo_delivery_commission_rate`/`seller_commission_rate`, `order_shipping_settlements.vendo_commission`, with a backfill of `cod_amount` for existing orders.
- `2026_10_28_110000_create_cod_cash_remittance_tables.php`: `order_cod_collections`, `cash_remittances`, `cash_remittance_items`.
- `2026_10_28_130000_add_resolution_to_cash_remittances.php`: `resolution_note`, `resolved_at`, `resolved_by`.

### Checkout and settlement

- `CheckoutController` and `Order` save `cod_amount`, `money_status = not_collected` and the three rate snapshots when an order is created.
- `ShippingEarningsSettlementService` uses the new `DeliveryFeeDistributionCalculator` for orders that carry `rider_pay_rate`: fixed Rider pay per delivered parcel, Vendo's percentage of the balance, Logistics remainder. Older orders keep the per-stop ₱10 rule.
- Buyer checkout and order pages show the cash amount due on delivery.

### Cash chain

- Rider API: `POST /api/v1/rider/delivery-proofs` takes `cash_collected` (required for new orders, must equal `cod_amount`), writes `order_cod_collections` and sets `money_status = collected_by_rider`; assignments expose `cash_to_collect`; new `GET /api/v1/rider/cash`.
- Station Hub **Cash Collections** and **Cash Discrepancies** (`lgp.station.cash*`): Rider ledger, receive with shortage/overage flag, resolve with a note, batch to the Province Hub.
- Province Hub **Cash Remittances** (`lgp.province.cash*`): confirm or dispute Station batches, resolve, batch to the Company.
- Company **Cash Remittances** and **Rider Payment** (`lgp.company.cash*`, `lgp.company.rider-pay*`): confirm Province batches, see the fee split on confirmed cash, send Vendo its share, edit `rider_pay_per_parcel`.
- Admin **Cash Remittances** (`admin.cash-remittances*`): confirm a Company batch to Vendo; a match moves its orders to `credited_to_seller`, a difference holds them in `discrepancy` until resolved.
- `CashRemittanceService` holds the shared receive/resolve logic plus the `provinceReady()` and `vendoDue()` queries; every step runs in a transaction with row locks and writes each order into at most one batch per level.

### Seller and Admin money views

- Seller **Earnings** (`seller.earnings.index`): per-order items, voucher, commission at the order's saved rate, net; Pending/Credited totals; `earnings_credited` notification when Vendo confirms the cash.
- Admin **Delivery Fees** (`admin.delivery-fees.*`): edits the two rates (super admin only), shows commission earned and a per-parcel distribution history.
- Admin Commission and Reports pages now use each order's saved seller rate instead of the fixed 10%.

### Documentation in the pull

New specs for [Seller earnings](../features/seller/earnings/spec.md), [Admin delivery fees and cash remittances](../features/admin/delivery-fees-and-remittances/spec.md), [Station Hub cash collections](../features/logistics/station-cash-collections/spec.md), and [Province Hub and Company cash](../features/logistics/province-company-cash/spec.md); a new [COD cash design](cod-cash-and-delivery-fee-distribution.md); updates to the shared commission spec, the Courier scan API, Buyer checkout, the Logistics navigation spec, `docs/schema.md`, `docs/domain-feature-status.md`, `docs/README.md`, and `docs/logs/PROGRESS-2026-10-29.md`.

## What is connected

Static checks in this review:

- `php artisan route:list` registers 446 routes, including all 23 new cash, earnings and delivery-fee routes; every route name referenced by the new Blade screens exists.
- `php -l` passes on every changed PHP file.
- `php artisan view:cache` compiles every Blade template, including the 8 new screens and `logistics/partials/cash-batches`, then the compiled views were cleared.

## What is not verified

- The three 28 October migrations are **Pending** on this machine; the local SQLite database is still at 23 October. Until `php artisan migrate` runs, any page or API that reads `delivery_fee_settings`, `order_cod_collections`, `cash_remittances` or the new `orders` columns will fail.
- No page was rendered, no request was made, no query was run against real data, and no money path was exercised. Arithmetic in `DeliveryFeeDistributionCalculator`, `SellerEarningsCalculator` and the raw SQL in the Admin pages is reviewed by reading only.
- The Rider mobile app (`E-Comm_Mobile`) does not send `cash_collected` yet, so new orders cannot be completed from the current app build.

## Notes and corrections

- The pulled progress log labels the Seller earnings work "step 8". The [design's implementation order](cod-cash-and-delivery-fee-distribution.md#8-implementation-order) numbers Seller earnings and the Buyer notice as step 7; step 8 is returns and failed-delivery reversals, which is not built.
- The design status line and several specs still described parts of this work as unbuilt or as foundation only. That text was corrected in the same pass; see the 29 October progress log.
- Seller vouchers remain Seller-funded and commission is still taken on the gross item price before the voucher. That is assumed in code, not a confirmed business decision (design section 9).
- Cash shortages are recorded as notes only; who bears a shortage is still undecided.
- Parcels whose settlement is held for `review` (route/snapshot mismatch) pay no Rider share, so the Company keeps the whole delivery fee.
