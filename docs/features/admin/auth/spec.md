# Admin Authentication

**Status:** Implemented with guard and account controls  
**Reviewed:** 24 September 2026

## Current behavior

Admin pages use the `admin` guard; login, password reset/verification, session tracking, active-account checks, and forced-password-change middleware are present.

## Gaps and acceptance direction

Review every exceptional route and ensure all admin mutations retain active-admin and password-change enforcement.

## Source evidence

`routes/auth.php`, `app/Http/Middleware/CheckAdminActive.php`, `app/Http/Middleware/ForcePasswordChange.php`, `app/Models/AdminLoginSession.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
