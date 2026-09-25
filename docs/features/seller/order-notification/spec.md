# Seller Order Notifications

**Status:** Implemented in-app; manual verification pending.
**Reviewed:** 25 September 2026

## Current behavior

Checkout writes a targeted `new_order` notification for each new seller order. Its link opens that seller's order detail. The dashboard shows recent activity, while the pinned header bell opens recent notifications and the full inbox.

## Gaps and acceptance direction

The inbox is scoped to the signed-in seller and supports an unread badge and mark-read actions. Checkout creates one notification per newly created seller order. Email and push delivery remain future work.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Communication/Notification.php`

## Related documentation

See [Seller Notifications](../notification/spec.md), [domain status](../../../domain-feature-status.md), and [feature implementation guide](../../../feature-implementation-guide.md).
