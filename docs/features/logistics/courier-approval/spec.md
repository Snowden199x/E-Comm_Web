# Courier Approval

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Center-scoped code checks the courier detail belongs to the logged-in center, then approves or rejects with reason/notes.

## Gaps and acceptance direction

Protect routes with authentication/role/approval middleware; wire rider registration from the intended app.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `app/Models/Profiles/CourierDetail.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
