# Logistics Shipment Status Updates

**Status:** Not implemented beyond status vocabulary  
**Reviewed:** 24 September 2026

## Current behavior

Order status values and status-event model support a broader timeline.

## Gaps and acceptance direction

No logistics status update route currently records sorting, dispatch, hub, or linehaul events.

## Source evidence

`app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
