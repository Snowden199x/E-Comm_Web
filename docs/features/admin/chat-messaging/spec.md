# Admin Messaging

**Status:** Implemented basic conversation operations  
**Reviewed:** 26 September 2026

## Current behavior

Admin can list conversations, fetch a thread, fetch updates, and send messages; shared message/attachment records exist.

Admin can delete a support conversation and its stored attachments from the support thread. Buyer and seller support deletion routes are absent. Threads and conversation lists show available profile photos with initials as a fallback. The notification bell offers **Mark all as read** next to **View all**.

## Gaps and acceptance direction

Confirm participant authorization, attachment handling, moderation, and complaint linkage rules.

## Source evidence

`app/Http/Controllers/Admin/MessageController.php`, `app/Models/Communication/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
## 25 September 2026 update

Open support threads refresh about once per second while visible. The conversation list refreshes separately so new buyer and seller support messages appear without reloading. Incoming support messages also create an admin notification. This uses short requests on the current Laravel server; broadcast infrastructure is not configured.
