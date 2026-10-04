# Admin Dashboard

**Status:** Implemented, reporting semantics need review  
**Reviewed:** 4 October 2026

## Current behavior

Shows platform order/sales/user counts, a six-week summary, pending Buyer/Seller/Logistics counts, recent registrations/complaints/notifications, and the latest published all-user announcement. Total Users includes Buyer, Seller and Logistics accounts. The route now applies the same active Admin and forced-password-change middleware as other Admin pages.

## Gaps and acceptance direction

Confirm whether gross sales should include cancelled, returned, or unpaid orders; use shared reporting definitions.

## Source evidence

`app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
