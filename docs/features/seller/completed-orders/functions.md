# Completed Orders — Functional Requirements

**Status:** Planned; no Completed Orders page exists yet. See [backend implementation spec](spec.md).

## Entry and purpose

The seller opens **Order Management → Completed Orders** to inspect orders delivered to buyers. Use the existing seller sidebar, top bar, summary cards, compact data table, and right-side drawer. The page title is **Completed Orders** and its subtitle should describe delivered orders.

## Visible functions

| Area | Intended function |
|---|---|
| Summary cards | Total completed orders, gross product revenue, unique customers, and delivery completion rate. |
| Search | Find an order by order ID, customer name, product name, or tracking reference. |
| Filters | Delivery date range, assigned courier, and recorded payment method; filters combine and persist through pagination. |
| Table | Order ID; buyer and location; product thumbnails and additional item count; order amount; delivered date/time; courier; delivered badge; View and overflow action; real result range and pagination. |
| Row View | Open an **Order Details** drawer while retaining the filtered list in the background. |
| Delivery Performance | Donut and counts for delivered, returned, cancelled, and current delivery-failed orders. **View report** opens the relevant report destination when implemented. |
| Top Products | Products ranked by quantity sold through delivered orders. **View all** opens the seller's full product or report list with the same seller scope. |

## Order Details drawer

The drawer shows the selected order's current ERP state and order ID; buyer name/contact and stored shipping address; assigned courier, Vendo tracking reference, optional carrier tracking number, delivery time, and a link to the seller shipment details; product images/names/SKUs/variations, quantity and line price; product subtotal, charged shipping fee, and payable total. **View Customer Feedback** opens Feedback filtered to this order. If there is no review, show a clear empty state rather than a sample review.

`Delivered` means the delivery event is recorded. `Completed` means the buyer has confirmed receipt. Both belong in this page, with the exact state available in the drawer. The seller cannot mark an order delivered or completed from this screen.

## Data and behavior

- Current checkout stores the product subtotal in `orders.total_amount` and charges zero shipping fee; display stored amounts only. Future nonzero fees must be charged through checkout before being shown as payable.
- Delivery date is the recorded transition to `delivered`, not the order creation date or an arbitrary `updated_at` timestamp. Older orders without a recorded delivery event must say **Date unavailable**.
- Each card, chart slice, product ranking, and pagination number comes from the seller's filtered database records.
- Empty, loading, unavailable tracking, and stale drawer states should be visually understandable; table and drawer are keyboard accessible.

See [seller Shipments](../shipments/spec.md), [ERP flow](../../../order-logistics-flow-decisions.md), and [Feedback](../review-management/spec.md).
