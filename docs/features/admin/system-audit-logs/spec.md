# Admin Audit Trail

**Status:** Implemented for the Admin decisions the application performs; browsable at `GET /admin/audit-logs`
**Reviewed:** 11 November 2026

## What is recorded

`admin_action_logs` stores the acting Admin, the target account, the action key, an optional reason and the timestamp; the row is written in the same transaction as the account change. Migration `2026_10_04_000003_create_admin_action_logs_table.php`.

Current writers: registration approve/reject; account suspend/deactivate/activate/suspension lift; account-deletion approve/reject; primary Logistics coverage set; Seller category-request approve/reject; product approve/reject/warn; commission-rate changes; policy create/update/delete; announcement create/update/delete; chat welcome changes; limit overrides (extra attempt, return window); support-ticket status; Logistics document approve/reject; Admin account create/suspend/reactivate/archive/restore/delete; complaint status; account-report approve/reject; review-report resolve/dismiss. Admin login/logout keep their own `admin_login_sessions` records.

## Viewer (`admin.audit-logs.index`, `GET /admin/audit-logs`)

- **Super admin only.** The sidebar entry appears only for `is_super_admin` accounts, and `AuditLogController@index` aborts with 403 for any other Admin, so the URL cannot be reached directly. Reason: the log names which Admin suspended, removed or overrode what.
- Filters: free-text search over the Admin, the account, email or reason; action select (only the actions present in the table); From/To dates. Filters and page number stay in the URL.
- Table: when (date and time), Admin (name and email), action chip, affected account (name, email, role) and the reason. 20 rows per page, newest first.
- Empty state distinguishes "no decision has been recorded yet" from "no match for these filters". A removed Admin or account still renders as "Removed admin" / "Deleted account" because the foreign keys null out.
- Human labels for the known action keys live in `AuditLogController::ACTION_LABELS`; an unknown key falls back to its readable form, so new writers show up without a schema change.

Platform-wide changes (rates, policies, announcements, chat welcome) are recorded with an empty target and show as **Platform setting**. Order-level overrides also keep their existing `orders.limit_override_*` columns and `order_status_events` note; the audit row names the Buyer and the order number.

## Gaps

- No export, no per-Admin drill-down, and no retry if a write is missed; the table is not enforced append-only at the database level.
- Non-super admins cannot read the log at all; there is no scoped "my own actions" view for them.
- Seller voucher changes, Seller/Escrow-side mutations and reads are not logged (reads are not audited anywhere).
- The viewer is read-only by design; there is no delete or edit action.

## Source evidence

`app/Http/Controllers/Admin/AuditLogController.php`, `app/Models/AdminActionLog.php`, `resources/views/admin/audit-logs/index.blade.php`, `resources/views/admin/partials/sidebar.blade.php`.

## Related documentation

See [User Management](../manage-user-accounts/spec.md), [domain status](../../../domain-feature-status.md), and the [Admin domain page](../../../domains/Admin.md).
