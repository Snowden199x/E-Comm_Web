# Logistics Center Dashboard

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Lists center-associated pending riders and counts pending/approved/rejected applications.

## Gaps and acceptance direction

Dashboard route currently lacks explicit authentication middleware; add center-role ownership checks to every access.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `resources/views/logistics/dashboard.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
