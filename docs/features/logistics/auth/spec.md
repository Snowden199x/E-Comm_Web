# Logistics Center Authentication

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Center registration uses email OTP, saves business/profile documents, and awaits admin approval. Shared logistics login accepts approved center/courier roles.

## Gaps and acceptance direction

Add correct guard/middleware to dashboard/action routes. Courier login currently redirects to an undefined named route.

## Source evidence

`app/Http/Controllers/Logistics/Auth/`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
