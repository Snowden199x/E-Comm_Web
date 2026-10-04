# Admin Notifications

**Status:** Implemented for in-app platform events; browser verification pending.
**Reviewed:** 4 October 2026

Admin notifications use records with `user_id = null`. Buyer- and seller-targeted notifications are excluded from the admin dashboard, bell and inbox. Admin receives notifications for new buyer, seller, and Logistics Center registrations; new product submissions and resubmissions for review; account reports submitted through `UserReportController`; product-review reports; and incoming Buyer/Seller support conversations or messages. The Logistics Center registration notification opens Registrations with the Logistics Center filter selected. A product submission notification opens Products for Review filtered to the product name. Saving a product as a draft does not notify Admin. Rider applications notify the assigned Logistics Center, which owns rider approval.

The Admin complaints screen has status and decision actions, but this review found no Buyer/Seller order-complaint submission route that creates a standard complaint record. The existing “Raise a Concern” support flow creates a support conversation and does notify Admin. A separate order-complaint intake event will need to create a complaint and notification if that workflow is added.

Order-placement notifications go to the Seller. Buyer and Seller shipment updates, review replies/moderation, and Logistics operational alerts go to their respective actors. Admin does not receive routine buyer order or fulfillment notifications. Buyer checkout no longer creates an admin `new_order` record; legacy `new_order` records are filtered out of the admin dashboard and notification inbox/bell.

The bell shows the unread count and polls for updates every three seconds while the page is visible. A new item triggers a short sound after the browser allows audio. This is near-real-time polling, not a WebSocket connection. The inbox shows newest items first, supports All and Unread filters, and lets an admin open, mark one read, or mark all read. Opening an item follows only an internal admin path; invalid links return to the inbox.

All admins currently share the same platform notification read state because the table has no admin recipient column. Personal admin notification preferences and external delivery channels are not implemented.

**Code:** `app/Http/Controllers/Admin/NotificationController.php`, `resources/views/admin/notifications/index.blade.php`, `resources/views/components/admin/layout.blade.php`.
