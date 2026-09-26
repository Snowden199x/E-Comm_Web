# Logistics Center Authentication

**Status:** Partial  
**Reviewed:** 26 September 2026

## Current behavior

Center registration uses email OTP, saves business/profile documents, and awaits admin approval. The dashboard, account page, and rider review actions require an authenticated, approved, active logistics-center account with a center profile. Shared logistics login accepts approved center/courier roles.

## Gaps and acceptance direction

Courier login currently redirects to an undefined named route. The rider app and its authentication contract remain future work.

## Source evidence

`app/Http/Controllers/Logistics/Auth/`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
