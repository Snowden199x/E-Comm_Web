# Seller Order Notifications

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Checkout writes a targeted new-order notification; seller dashboard includes pending-order and notification/announcement content.

## Gaps and acceptance direction

Unread/read lifecycle and delivery channels (email/push) are not a full implementation.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Communication/Notification.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
