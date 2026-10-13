# Buyer Notifications

**Status:** Implemented; Seller voucher alerts added 13 October 2026; owner verification pending
**Reviewed:** 13 October 2026

## Current behavior

Buyers see only their own notifications. The Notifications page (`buyer.notifications.index`) has:

- **All** and **Unread** filters, with an unread count badge;
- notifications grouped by Today, Yesterday, This week, and Earlier within the current page of 15;
- an icon by type (order, message, review, announcement, warning, general), an unread dot, a bold title for unread items, a two-line message, and a relative time (the full date on hover);
- **View**, which marks the notification read and opens its destination, and **Mark as read** for unread items;
- **Mark all as read**, and pagination;
- empty states for "No notifications yet" and "You are all caught up".

Account warnings and announcements open the notification detail dialog (`shared.notification-detail`), including the policy text for policy updates. Case message notifications open the Buyer's private case thread after the route checks case participation. The header bell shows the five latest in a dropdown with an unread dot, refreshed by the existing polling. Destinations are limited to Buyer pages by `NotificationController::destination()`.

## Gaps and acceptance direction

The notification sound preference now persists in `buyer_settings` and is read by the Buyer layout. It controls the browser chime; selecting which notification categories or emails to receive is still unavailable.

When a Seller first activates a voucher, Buyers with a prior order from that Seller receive one notification per Buyer/voucher. The notice includes the code and scope, links to the Seller's shop, and does not include order details. A unique recipient record and first-activation timestamp prevent duplicate alerts. At checkout, the voucher selector shows currently redeemable offers whose shop/product/category scope matches the selected cart lines. See [Buyer voucher usage](../voucher-usage/spec.md).

The icon is chosen from keywords in the notification `type` (order, message, review, announcement, warning) and falls back to a general bell. A fixed list of types or a category column would make this dependable. There is no delete or archive action. Grouping applies to the current page only. See [backend needs](../../../backend-needs.md), item 8.

## Source evidence

`resources/views/buyer/notifications/index.blade.php`, `resources/views/buyer/notifications/recent.blade.php`, `resources/views/shared/notification-detail.blade.php`, `app/Http/Controllers/Buyer/NotificationController.php`, `app/Models/Communication/Notification.php`

## Related documentation

See [Seller notifications](../../seller/notification/spec.md), [Admin notifications](../../admin/notification/spec.md), and [domain status](../../../domain-feature-status.md).
