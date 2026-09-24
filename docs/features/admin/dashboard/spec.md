# Admin Dashboard

**Status:** Implemented, reporting semantics need review  
**Reviewed:** 24 September 2026

## Current behavior

Shows platform order/sales/user counts, a six-week summary, pending buyer/seller counts, recent registrations/complaints/notifications, and the latest published all-user announcement.

## Gaps and acceptance direction

Confirm whether gross sales should include cancelled, returned, or unpaid orders; use shared reporting definitions.

## Source evidence

`app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
