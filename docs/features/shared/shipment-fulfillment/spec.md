# Shipment Fulfillment

**Status:** Partial foundation  
**Reviewed:** 24 September 2026

## Current behavior

Orders have courier reference, status vocabulary, status history, and seller handoff actions.

## Gaps and acceptance direction

A shipment/parcel aggregate and complete logistics/courier transitions remain future work.

## Source evidence

`app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
