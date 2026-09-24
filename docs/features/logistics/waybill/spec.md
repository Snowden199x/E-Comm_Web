# Waybill

**Status:** Partial: seller print view exists  
**Reviewed:** 24 September 2026

## Current behavior

Seller can open a print-friendly waybill for owned eligible orders.

## Gaps and acceptance direction

No logistics-owned generation, barcode/QR scan, hub manifest, or carrier integration is present.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/waybill.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
