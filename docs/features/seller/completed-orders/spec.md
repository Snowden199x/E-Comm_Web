# Seller Completed Orders — backend implementation contract

**Status:** Implemented in the seller web app.
**Functional requirements:** [Completed Orders functions](functions.md).  
**Scope:** One existing `orders` row represents one seller order/shipment. This page is a read-only seller view of delivery outcomes and does not create a second completed-order table.

## Existing code to reuse

- `App\Models\Ecommerce\Order` already has seller/buyer/courier relations, items, `statusEvents`, `tracking_number`, carrier fields, `shipping_fee`, and `SALES_STATUSES = ['delivered', 'completed']`.
- `GET /seller/shipments/{order}` already provides seller-owned shipment detail. Buyer `POST /buyer/orders/{order}/complete` makes `delivered → completed`; logistics/courier delivery recording is not yet implemented.
- `orders.total_amount` currently holds the product subtotal, and COD checkout initializes `shipping_fee` to zero. `order_items.price` snapshots the sale price.
- The seller layout links to the seller-scoped Delivered Orders page; the existing `orders` records remain the source of order state.

## Routes, controller, and permissions

| Method/path | Responsibility |
|---|---|
| `GET /seller/completed-orders` (`seller.completed-orders.index`) | Seller-scoped counts, filters, table, performance, and top products. |
| `GET /seller/completed-orders/{order}` (`seller.completed-orders.show`) | Seller-owned order detail drawer; return 404 for another seller's ID or an order outside `delivered/completed`. |

Place both under existing `auth` and `EnsureActiveSeller` middleware. Scope the base query by `seller_id = auth()->id()` before applying any search, aggregate, or detail lookup. Do not accept `seller_id` from the browser. Use eager loads and bounded pagination; avoid loading all orders for the donut or top products. All output is escaped in Blade. The page has no seller status-change, refund, or delivery-confirmation endpoint.

## Data and filter contract

- Base table statuses: `delivered` and `completed`. Count each order once, including an order whose buyer later confirmed receipt. Exclude orders currently `returned` or `cancelled` from sales totals.
- Search (trimmed, bounded string) matches `VN-000123`/numeric order ID, persisted `VND-...` reference, optional carrier tracking, buyer name, or item product name. Seller scope applies to nested item/product searches too.
- Delivery date range uses a recorded `delivered_at` timestamp. Add nullable indexed `orders.delivered_at`; backfill from the earliest `order_status_events` transition **to** `delivered` when one exists. For every future delivery transition, set this field in the same transaction as status and event. Do not infer missing historical delivery times from `orders.created_at` or `updated_at`. Such rows remain in unfiltered results with **Date unavailable** and are excluded by a delivery-date filter.
- Courier dropdown lists couriers present in this seller's orders, plus **Unassigned**; filter by `courier_id`. Render carrier company and rider name distinctly if both exist.
- Payment dropdown lists values actually present in scoped orders (`cod` initially); avoid showing unsupported payment methods. Validate selected values rather than trusting arbitrary query data.
- Date range, courier, payment and search combine. Preserve query parameters in pagination (eight rows/page). Sort by `delivered_at DESC`, then order ID DESC, placing unknown dates last.
- Cards use search/date/courier/payment scope. Table and top-products use only current delivered/completed orders. **Total completed orders** counts both states; **unique customers** counts distinct `buyer_id`.

## Money, rate, and chart definitions

| Display | Calculation |
|---|---|
| Gross product revenue | Sum `orders.total_amount` for the filtered delivered/completed set, in PHP cent-safe decimal or SQL decimal arithmetic. It excludes shipping, commission, and refunds. Label it as gross product revenue if the compact card cannot explain the amount. |
| Order subtotal | Sum `order_items.quantity × order_items.price`, normally equal to stored `orders.total_amount`; report mismatches rather than silently changing history. |
| Charged shipping | `orders.shipping_fee` already persisted at checkout; currently zero. The seller page cannot alter it. |
| Payable total | Stored product subtotal plus the shipping fee actually charged for that order. Do not double-add a fee if a future checkout redefines `total_amount`; update this contract with that change. |
| Delivery performance | Current-status buckets: delivered (`delivered` + `completed`), returned, cancelled, and delivery failed. One order belongs to one bucket. Other in-progress states are outside this chart. |
| Completion rate | `delivered / (delivered + returned + cancelled + delivery_failed) × 100` within the same non-status filters; show 0%/empty chart for zero denominator. This is a current outcome rate, not a guarantee that all deliveries will stay final. |
| Top products | Group `order_items` belonging to filtered delivered/completed orders by `product_id`, sum quantity, order descending, stable ID tie-break. Use a retained item snapshot or an **Unavailable product** label if a linked product is absent; never mix other sellers' orders. |

For the chart, date filtering uses the time the **current** outcome was recorded: `delivered_at` for delivered/completed, the most recent matching status event for returned/cancelled/delivery_failed. Legacy outcomes without events remain visible in an unfiltered chart but are excluded by a date filter. This keeps period counts honest. Card totals and top products use delivered orders only; chart additionally includes the three exception groups.

## Drawer and navigation

Load buyer, courier, items and product images, and status history for the seller-owned order. Display recorded delivery event time, exact status, tracking details, shipping address, line totals, and status timeline. **View Tracking Details** opens the existing seller Shipment detail for the same order. **View Customer Feedback** opens `/seller/feedback?order={id}` after Feedback is implemented; show the review count/empty state from actual reviews. Row overflow may offer View, View shipment, and View feedback only; there is no seller-side refund or delivery mutation on this page.

## Dependencies and implementation sequence

1. Add `delivered_at` migration and update the future logistics/courier transition that records `delivered`; backfill only from real history. The Completed Orders list may be built before that transition exists, but will remain empty until real deliveries are recorded.
2. Add seller controller/routes/query aggregation and detail partial. Reuse the seller layout and shipment drawer patterns, keeping pagination, loading and empty states.
3. Wire sidebar and Feedback link. When Feedback is not yet available, hide or disable that link with a clear explanation; do not link to `#`.
4. Expose the same definitions to future reports or mobile API through shared query/service code to prevent metric drift.

[Feedback backend plan](../review-management/spec.md) · [Seller domain](../../../domains/Seller.md) · [Order flow](../../../order-logistics-flow-decisions.md)
