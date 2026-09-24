# Admin Account Management

**Status:** Implemented with sensitive operations  
**Reviewed:** 24 September 2026

## Current behavior

Supports admin list/detail/create/update, force-password change, temporary-password display, suspend/reactivate, reset-link send, restore/force-delete, and profile/password updates.

## Gaps and acceptance direction

Review privilege separation for super-admin actions, one-time password handling, session revocation, and audit coverage.

## Source evidence

`app/Http/Controllers/Admin/AccountManagementController.php`, `app/Models/AdminLoginSession.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
