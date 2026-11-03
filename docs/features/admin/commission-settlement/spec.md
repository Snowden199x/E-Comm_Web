# Commission Settings

**Status:** Partial: per-order seller-rate commission reports and editable rates exist; no settlement ledger
**Reviewed:** 29 October 2026

## Current behavior

Admin can inspect Seller/month merchandise totals and calculated commission. Calculations include item price × quantity for orders in `delivered` or `completed` status and apply each order's saved seller commission rate to that gross item value. The rate is editable on the Admin Delivery Fees page (`delivery_fee_settings.seller_commission_rate`, default 10) and the old Commission POST endpoint is still gone. Seller vouchers reduce the Buyer's payable total, but currently do not reduce the Admin commission base. The displayed amount is a report calculation, not a payable balance, and is separate from the Logistics/Rider shipping earnings.

## Commission page redesign (31 October 2026)

- **Layout.** Heading and month switcher, four cards (Completed sales and Commission earned with the change against last month, Commission rate with an **Edit rate** link to Delivery Fees, Sellers with sales as "x of y"), a search box and one seller table. The next-month arrow is disabled on the current month and a future month in the URL falls back to the current month.
- **Table.** Seller, Orders, Sales, Share, Commission and a **View breakdown** button per row (the row click still works). Sellers are sorted by commission, highest first; sellers without sales come last, A to Z. 10 per page (was 8). Seller status chips follow the admin design system (suspended = warning, deactivated and not approved = neutral).
- **Breakdown dialog.** Completed sales, Commission and Average rate, then a product table with a share bar. The chart was removed because the table shows the same figures. The footer Close button was removed; the X button, Escape and a click outside close it.
- **States.** Skeletons for the cards and table when a month, search or page change takes longer than 150 ms, and for the dialog. Empty states: no sellers, no search match (with **Clear search**), no completed sales in the month (with **Go to {previous month}**), no sales in the dialog. Errors say what to do (offline, session ended, access, server error) with **Retry**.
- **Backend.** `CommissionController` now ranks sellers in one query (a sales and a commission subquery per seller) instead of one query per seller. Month input is validated. `sellerDetail` returns `average_rate` and answers 404 for a user who is not a seller. The calculation, the routes and the schema are unchanged.

## Delivery fee and seller commission rates (28 October 2026)

The seller commission is now an editable setting (`delivery_fee_settings.seller_commission_rate`, default 10) on the Admin Delivery Fees page, and each new order saves the rate in force. This page and the Reports now calculate commission per order from the saved rate (`COALESCE(orders.seller_commission_rate, 10)`), so a rate change never alters past commissions; `CommissionSetting::currentRate()` now returns the current setting for display. See the [Admin delivery fees and cash remittances spec](../delivery-fees-and-remittances/spec.md).

## Gaps and acceptance direction

Each order now carries the seller rate it was placed with, so past commissions do not move when the rate changes; there is still no commission ledger row per order and no invoice. Route-leg tariffs, Logistics/Rider beneficiary allocation, payout/settlement ledger, reconciliation, reversals, and disbursement workflow are not implemented. The policy for funding Seller vouchers and settling the Seller's net proceeds also needs a decision. See the [commission calculation and settlement design](../../../design/commission-calculation-and-settlement.md) for the Aisley-based shipping reference and Vendo-specific boundaries.

## Source evidence

`app/Http/Controllers/Admin/CommissionController.php`, `app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
