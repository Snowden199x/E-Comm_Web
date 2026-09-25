# Seller Reports

**Status:** Implemented in code; manual verification pending.
**Scope:** Preview and download the authenticated seller's operational sales report for Today, This Week, This Month, or This Year.

## Current code

The seller Reports page now has period filters, summary cards, a sales chart, top products, delivered order rows, a preview and PDF download. Admin has a separate report with platform-wide figures. Seller Reports uses only the signed-in seller's delivered orders.

## Seller page

Keep the seller sidebar and topbar. The page opens with **Today** selected, a period selector, four summary cards, a sales chart, a top-products table, and the included order rows. Preview and Download PDF each offer Today, This Week, This Month, and This Year, consistent with Admin Reports. **Preview** loads the selected period into a modal on the same page; the modal also offers a download of that report. **Download PDF** exports the selected period. Changing the page period updates its summary and URL; each report action uses the period chosen in its own menu. Show an empty report with zero totals when the period has no deliveries.

| Period | Boundary in the app timezone | Chart grouping |
|---|---|---|
| Today | Start of today through the current time | Hour |
| This Week | Monday 00:00 through the current time | Day |
| This Month | First day 00:00 through the current time | Day |
| This Year | January 1 00:00 through the current time | Month |

Display the actual start and end timestamps on the preview and PDF, along with generation time. Use the same `Asia/Manila` period boundary throughout the query, chart, and filename. A custom historical date range can follow later without changing these four presets.

## Report definitions

Start every query with `orders.seller_id = auth()->id()`. A sale counts once when its **current** status is `delivered` or `completed` and its recorded `delivered_at` falls in the period. Buyer confirmation must not count it again. Orders without a recorded delivery time remain visible in Delivered Orders but do not enter a dated report. Returned, cancelled, and failed orders are excluded from sales totals and shown as separate outcome counts when their status-event time falls in the period.

| Display | Definition |
|---|---|
| Gross product revenue | Sum of `orders.total_amount` for included orders; this field currently stores the product subtotal. |
| Delivered orders | Count of included order IDs. |
| Units sold | Sum of `order_items.quantity` for included orders. |
| Average order value | Gross product revenue divided by delivered orders; show `—` when there are none. |
| Top products | Sum units and `quantity × price` by product ID from the included order items; show a fallback name if a product is unavailable. |
| Shipping charged | Sum of stored `orders.shipping_fee`, displayed separately from product revenue. |

The report must not call gross revenue a payout or net earnings. The current `CommissionSetting` stores only a present-day rate; there is no per-order commission snapshot, payout ledger, or refund amount. Do not calculate historical commission or net payout from that mutable rate. Returned-order counts are informational until return/refund accounting is recorded. Do not include buyer phone numbers, identity documents, or full addresses in the PDF.

## Backend contract

`GET /seller/reports`, `GET /seller/reports/preview`, and `GET /seller/reports/download` run under `auth` and `EnsureActiveSeller`. The controller validates `period` against `today`, `week`, `month`, and `year`; it does not accept a seller ID from the browser. One report builder feeds the page, preview and PDF. The web list is paginated; preview and PDF list up to 100 orders while their totals cover the whole period. Blade escapes seller and product names.

The seller sidebar links to the report route. Admin report routes and platform-wide calculations remain separate. The same definitions can later serve a mobile API.

**Related:** [Delivered Orders](../completed-orders/spec.md) · [Seller domain](../../../domains/Seller.md) · [Commission and settlement](../../shared/commission-settlement/spec.md)
