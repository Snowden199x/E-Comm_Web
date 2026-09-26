# Seller Order Management

**Status:** Implemented for seller-owned orders  
**Reviewed:** 26 September 2026

## Current behavior

Real order list, groups, search/date filters, pagination, details/history, and seller-safe order scoping are present. Seller actions stop at ready for pickup; the drawer distinguishes pickup and delivery riders after delivery assignment. The seller-owned print route produces a 4 × 6 inch label with the assigned origin logistics center's business name, destination hub or buyer area, addresses, Code 128 barcode and QR for the Vendo tracking number. Printing is available after readiness and origin assignment; before then, the visible control explains why it is disabled.

## Gaps and acceptance direction

Center arrival, sorting, hub send/receipt, and delivery-rider assignment have logistics endpoints. Assigned rider pickup, origin arrival, and out-for-delivery scans have a versioned API for the later separate rider client. Courier acceptance, proof, and delivery completion remain open.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
