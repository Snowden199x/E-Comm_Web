# Seller Order Management

**Status:** Implemented for seller-owned orders  
**Reviewed:** 5 October 2026 (UI refresh; behavior unchanged)

## Current behavior

Real order list, groups, search/date filters, pagination, details/history, and seller-safe order scoping are present. Seller actions stop at ready for pickup; the drawer distinguishes pickup and delivery riders after delivery assignment. The seller-owned print route produces a 4 × 6 inch label with the assigned origin logistics center's business name, destination hub or buyer area, addresses, Code 128 barcode and QR for the Vendo tracking number. Printing is available after readiness and origin assignment; before then, the visible control explains why it is disabled.
An unsent decline reason in the order drawer survives a same-tab reload for up to two hours and clears after a successful action. See [form reload recovery](../../shared/form-draft-recovery/spec.md).
Seller can decline a new order or cancel an order through `ready_for_pickup` from the Orders drawer or Shipments detail. A reason modal records a preset reason and optional details; Other requires details. Cancellation restores reserved stock and notifies the Buyer, assigned rider, and pending pickup Logistics Center.

## Orders page UI (5 October 2026 refresh)

Frontend only. Route names, form field names, element ids, `data-` hooks, JSON shapes and controller data are unchanged.

- **Header and live indicator:** page title plus a Live badge with the time of the last refresh. The page still refreshes every 3 seconds; those silent refreshes never replay animations or flash the loading bar.
- **Stat cards** (New Orders, To Pack, Ready for Pickup, Pending Deliveries) double as quick filters and highlight when active. Counts animate briefly when they change.
- **Tabs and filters:** tabs with count badges and a sliding underline, search (order ID or customer, with a clear button), date presets/range, a status dropdown, and a **Clear filters** link when any filter is active. The URL is kept in sync.
- **Table:** order ID, customer with initials avatar, item count (product thumbnails when the controller eager-loads `items.product.images`; hidden otherwise), total, status pill with dot, date/time, View button. Rows enter with a short staggered animation on user-initiated loads only.
- **Empty states:** separate messages for no orders at all (with a link to Products), no results for the current filters (with Clear filters), and each status tab (new, to pack, ready for pickup, pending delivery, delivered, cancelled, returned).
- **Order drawer:** status and total, a progress track (Placed → Accepted → Packing → Ready → In transit → Delivered; cancelled/returned orders show a notice instead), customer card with profile link, address and couriers, product cards (image, variation, SKU, price × quantity, line total), payment summary (shipping line only when greater than zero), history timeline and a pinned action bar. The drawer slides in beside the table on wide screens and becomes a slide-over sheet below 980 px. The decline/cancel reason dialog is unchanged in behavior.
- Motion respects `prefers-reduced-motion`.

### Order number format (planned, not implemented)

The owner wants Seller-visible order IDs like `ORD-2026-0001`. The live format is still `VN-` plus the zero-padded database id (`Order::getNumberAttribute()`). Every Seller view reads `$order->number`, so they will pick up the new format automatically once the backend generates it. Requirements are in [backend needs](../backend-needs.md). Do not describe the new format as live until then.

## Gaps and acceptance direction

Center arrival, sorting, hub send/receipt, and manual delivery-rider assignment have Logistics Center web endpoints. The assigned Rider does not accept or decline work; pickup, origin arrival, transitional SOC-coded routing, destination leg, out-for-delivery, and delivered scans use the versioned mobile API. The delivered scan is a Rider report; buyer receipt confirmation remains a separate action. Physical SH scans, truck movement/manifests, and independent delivery proof remain open. Rider and Truck interfaces are mobile-only.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/{index,rows,drawer}.blade.php`, `resources/css/seller/order-management-orders.css`, `resources/js/seller/order-management-orders/index.js`

## Related documentation

See [backend needs](../backend-needs.md), [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).