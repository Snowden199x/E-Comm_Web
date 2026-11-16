# Admin Authentication

**Status:** Implemented with a hidden Admin entry, guard, and account controls
**Reviewed:** 16 November 2026

## Current behavior

Admin and Super Admin pages are mounted on a configurable path from `ADMIN_PATH` (default `vendo-control`), not the predictable `/admin` segment. `ADMIN_PATH` is loaded into `config('app.admin_path')`; changing it requires rebuilding the route cache. The old `/admin` entry is intentionally unrouted and falls through to the branded 404 page, so visiting it does not reveal the new path. This is URL obscurity, not a replacement for authentication.

Admin pages use the separate `admin` guard; login credential lookup is constrained to `role=admin`, so Buyer, Seller and Logistics accounts cannot sign in through the Admin or Super Admin form. Super Admin is an Admin account with the additional `is_super_admin` privilege; web-role sessions do not authenticate the `admin` guard. Regular Admin accounts remain part of the console design; super-admin-only operations still reject them with 403.

Admin self-registration and the unused Admin email-verification routes were removed. Admin accounts are provisioned by the Super Admin through Account Management. The Admin account-list partial at `admin.account-management.table` now applies the same `is_super_admin` check as the other account-management mutations instead of relying on the UI to hide it.

Password reset, session tracking, active-account checks, and forced-password-change middleware are present. The password-confirmation screen uses the Vendo Admin login styling and submits to the namespaced `admin.password.confirm` route.

## Gaps and acceptance direction

`ADMIN_PATH` hides the entry but does not add a second factor or restrict regular Admin accounts. Review whether the project still needs multiple Admin accounts, add two-factor authentication if required, and review every exceptional route for active-admin and password-change enforcement.

## Source evidence

`routes/auth.php`, `routes/web.php`, `config/app.php`, `app/Http/Middleware/CheckAdminActive.php`, `app/Http/Middleware/ForcePasswordChange.php`, `app/Http/Controllers/Admin/AccountManagementController.php`, `app/Models/AdminLoginSession.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
