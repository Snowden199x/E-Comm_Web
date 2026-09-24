# Admin Messaging

**Status:** Implemented basic conversation operations  
**Reviewed:** 24 September 2026

## Current behavior

Admin can list conversations, fetch a thread, fetch updates, and send messages; shared message/attachment records exist.

## Gaps and acceptance direction

Confirm participant authorization, attachment handling, moderation, and complaint linkage rules.

## Source evidence

`app/Http/Controllers/Admin/MessageController.php`, `app/Models/Communication/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
