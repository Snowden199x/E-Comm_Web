# Order Cancellation/Modification

**Status:** Not implemented for buyer  
**Reviewed:** 24 September 2026

## Current behavior

Seller decline of a newly placed order restores stock and records a reason.

## Gaps and acceptance direction

No buyer cancellation, change request, refund, or cancellation cutoff policy was found. Do not expose an implied buyer cancellation action until rules exist.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `app/Http/Controllers/Buyer/OrderController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
