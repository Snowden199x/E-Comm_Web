# Courier Approval

**Status:** Partial  
**Reviewed:** 26 September 2026

## Current behavior

Approved, active logistics centers can approve or reject pending courier applications linked to their center. Review actions require authentication and a center profile; wrong-center or non-courier records are denied, and already-reviewed applications are rejected under a row lock.

## Gaps and acceptance direction

The rider registration app and private verification-document delivery remain future work. Review decisions do not yet have a dedicated audit record.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `app/Models/Profiles/CourierDetail.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
