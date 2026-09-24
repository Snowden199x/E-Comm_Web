# Seller Order Management

**Status:** Implemented for seller-owned orders  
**Reviewed:** 24 September 2026

## Current behavior

Real order list, groups, search/date filters, pagination, details/history, waybill, and seller-safe order scoping are present.

## Gaps and acceptance direction

Logistics-owned states still have no logistics action endpoints; confirm waybill requirements with logistics spec.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `resources/views/seller/order-management-orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
