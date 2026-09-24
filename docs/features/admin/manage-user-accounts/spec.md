# User Management

**Status:** Implemented for buyer/seller accounts  
**Reviewed:** 24 September 2026

## Current behavior

Lists approved/suspended/deactivated and rejected buyer/seller users with search/type/date filters; supports suspend for seven days, deactivate, and activate.

## Gaps and acceptance direction

Current listing query excludes logistics centers/couriers/admins; document that scope or expand it deliberately. Add audit history and boundary tests.

## Source evidence

`app/Http/Controllers/Admin/UserManagementController.php`, `resources/views/admin/user-management/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
