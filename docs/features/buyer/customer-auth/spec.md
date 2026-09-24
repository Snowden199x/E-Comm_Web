# Buyer Authentication

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Buyer login/logout and shared password reset routes exist; buyer protected pages require authentication.

## Gaps and acceptance direction

Verify role checks on every buyer route, since `auth` alone is not the same as buyer-role authorization.

## Source evidence

`app/Http/Controllers/Buyer/AuthenticatedSessionController.php`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
