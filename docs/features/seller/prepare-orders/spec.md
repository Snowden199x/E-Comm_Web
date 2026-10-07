# Prepare Orders

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Accepted order can move from confirmed to preparing and then ready for pickup; transitions create status events.

## Gaps and acceptance direction

Packaging checklist, partial fulfillment, and split parcels are not modeled.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
