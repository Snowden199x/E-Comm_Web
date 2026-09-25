# Admin Notifications

**Status:** Implemented for in-app platform events; browser verification pending.
**Reviewed:** 25 September 2026

Admin notifications use records with `user_id = null`. Buyer- and seller-targeted notifications are excluded from the admin dashboard, bell and inbox. New buyer and seller registrations and incoming support messages create admin records.

The bell shows the unread count and refreshes while the page is open. A new item triggers a short sound after the browser allows audio. The inbox shows newest items first, supports All and Unread filters, and lets an admin open, mark one read, or mark all read. Opening an item follows only an internal admin path; invalid links return to the inbox.

All admins currently share the same platform notification read state because the table has no admin recipient column. Personal admin notification preferences and external delivery channels are not implemented.

**Code:** `app/Http/Controllers/Admin/NotificationController.php`, `resources/views/admin/notifications/index.blade.php`, `resources/views/components/admin/layout.blade.php`.
