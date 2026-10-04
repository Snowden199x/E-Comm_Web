# Seller Order Management

**Status:** Implemented for seller-owned orders  
**Reviewed:** 27 September 2026

## Current behavior

Real order list, groups, search/date filters, pagination, details/history, and seller-safe order scoping are present. Seller actions stop at ready for pickup; the drawer distinguishes pickup and delivery riders after delivery assignment. The seller-owned print route produces a 4 × 6 inch label with the assigned origin logistics center's business name, destination hub or buyer area, addresses, Code 128 barcode and QR for the Vendo tracking number. Printing is available after readiness and origin assignment; before then, the visible control explains why it is disabled.
An unsent decline reason in the order drawer survives a same-tab reload for up to two hours and clears after a successful action. See [form reload recovery](../../shared/form-draft-recovery/spec.md).
Seller can decline a new order or cancel an order through `ready_for_pickup` from the Orders drawer or Shipments detail. A reason modal records a preset reason and optional details; Other requires details. Cancellation restores reserved stock and notifies the Buyer, assigned rider, and pending pickup Logistics Center.

## Gaps and acceptance direction

Center arrival, sorting, hub send/receipt, and manual delivery-rider assignment have Logistics Center web endpoints. The assigned Rider does not accept or decline work; pickup, origin arrival, transitional SOC-coded routing, destination leg, out-for-delivery, and delivered scans use the versioned mobile API. The delivered scan is a Rider report; buyer receipt confirmation remains a separate action. Physical SH scans, truck movement/manifests, and independent delivery proof remain open. Rider and Truck interfaces are mobile-only.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
