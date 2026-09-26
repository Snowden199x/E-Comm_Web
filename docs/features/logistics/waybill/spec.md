# Waybill

**Status:** Seller-owned print label implemented; mobile scanner pending
**Reviewed:** 26 September 2026

## Current behavior

Seller can print a 4 × 6 inch shipping label for an owned order after it is ready for pickup and its origin logistics center resolves. The primary carrier wordmark uses that center's `business_name`, so an order handled by JNK displays JNK. The destination block uses the assigned hub when known, otherwise the buyer's destination area with a pending-hub note. Buyer/seller addresses, COD amount, Code 128 barcode and QR are generated from the order. Both codes encode only the stable Vendo tracking number. Before the origin is assigned, the print control stays visible but disabled with a reason. Printing does not expose an unauthenticated order lookup.

## Gaps and acceptance direction

No uploaded logistics logo image, hub manifest, carrier API, or physical print verification is present. The assigned rider scan backend is described in [the courier scan contract](../../courier/scan-api/spec.md); the separate rider client remains future work.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/waybill.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
