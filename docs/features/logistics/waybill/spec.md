# Waybill

**Status:** Seller-owned print label implemented; rider API/client available; SH locality scanner integration pending
**Reviewed:** 28 September 2026

## Current behavior

Seller can print a 4 × 6 inch shipping label for an owned order after it is ready for pickup and its origin logistics center resolves. The primary carrier wordmark uses that center's `business_name`, so an order handled by JNK displays JNK. The destination block uses the assigned hub when known, otherwise the buyer's destination area with a pending-hub note. Buyer/seller addresses, COD amount, Code 128 barcode and QR are generated from the order. Both codes encode only the stable Vendo tracking number. Before the origin is assigned, the print control stays visible but disabled with a reason. Printing does not expose an unauthenticated order lookup.

## Gaps and acceptance direction

No uploaded logistics logo image, truck manifest, carrier API, or physical print verification is present. The current mobile rider API/client handles seller pickup, transitional SOC-coded stages, destination delivery and delivery reporting. SH codes on configured routes refer to named localities such as Pagsanjan/SH3 and do not have exact addresses. Actual locality arrival scans and truck/local-rider handoffs need a separate backend contract; the planned scanner app belongs to a separate repository that the owner will provide later. Courier/rider/truck interfaces are mobile-only. See [the current rider API contract](../../courier/scan-api/spec.md) and [virtual SH routing](../subhub-routing/spec.md).

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/waybill.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
