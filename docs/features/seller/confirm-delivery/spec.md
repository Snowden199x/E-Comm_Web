# Confirm Pickup Handover

**Status:** Partial / seller confirms courier pickup  
**Reviewed:** 24 September 2026

## Current behavior

Seller can mark ready order picked up only if a courier assignment exists.

## Gaps and acceptance direction

Assignment flow is absent; the seller should not be the authority for delivery completion. Buyer confirms delivered order completion.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `app/Http/Controllers/Buyer/OrderController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
