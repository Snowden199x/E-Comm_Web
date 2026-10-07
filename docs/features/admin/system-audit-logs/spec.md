# Administrative Audit Trail

**Status:** Partial — account review/status actions logged; other Admin mutations pending
**Reviewed:** 4 October 2026

## Current behavior

Admin login-session records include login/logout and device information.
Migration `2026_10_04_000003_create_admin_action_logs_table.php` adds actor Admin ID, target user ID, action, reason and timestamps. Registration approval/rejection and User Management suspend/deactivate/activate write an audit row in the same transaction as the account change.

## Gaps and acceptance direction

Policy edits, commission changes, product moderation and other Admin mutations are not yet recorded here. There is no audit browsing screen. Preserve the append-only record and never use mutable notification rows as the audit trail. Owner migration and verification are pending.

## Source evidence

`app/Models/AdminLoginSession.php`, `database/migrations/*admin_login_sessions*`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
