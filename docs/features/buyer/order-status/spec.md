# Order History and Tracking

**Status:** Partial but buyer timeline is wired  
**Reviewed:** 24 September 2026

## Current behavior

Buyer sees owned orders and status events; buyer can confirm an order after the status reaches delivered.

## Gaps and acceptance direction

Logistics/courier transitions are not yet supplied; failed delivery and return/refund handling need implementation.

## Source evidence

`app/Http/Controllers/Buyer/OrderController.php`, `resources/views/buyer/orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
