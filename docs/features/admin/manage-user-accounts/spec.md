# User Management

**Status:** Implemented for buyer, seller and logistics-center accounts (UI redesigned 4 October 2026; file popups added 6 October 2026; owner verification pending)  
**Reviewed:** 6 October 2026

## Current behavior

The Admin User Management list and counts cover buyer, seller, and logistics-center accounts with `status = approved`. Their `account_status` distinguishes active, suspended, and deactivated accounts. The "Rejected users" toggle switches it to rejected (`status = disapproved`) accounts. Pending applications stay in Admin Registrations.

**List (`admin.user-management.index`).** Count cards for all users, sellers, buyers, and logistics centers; selecting a card filters the list to that type. Search by name or email, a date filter, a user-type filter, and the Rejected toggle refresh the list **in place**: the browser fetches the index page and swaps only `#um-region`, which holds the table and every per-user dialog together, so the dialogs always match the visible rows. Open dialogs are closed on each refresh. Eight rows per page, a windowed page list, and the URL is kept in sync. Status pills read Active, Suspended, Deactivated, or Rejected. Selecting a row or a name opens that user's profile.

**Profile (modal only, never a separate page).** A left card shows the avatar initial, role, status, email, phone, business name (sellers and logistics centers), category chips colored by `Category::colors` (sellers), and Date Joined. Below it are the actions for the user's status. The right side stacks Personal Information (with the valid ID preview and enlarge viewer), Address, and, for sellers and logistics centers, Business Information. Street and house number display as one "Street / House No." line. IDs and permits open through the protected Admin document route **in the shared document popup** (no new tab); images and PDFs both display, and a missing file shows a specific reason. See [Admin document viewer](../document-viewer/spec.md).

**Actions.** Admin can suspend, deactivate, and reactivate these roles; each action opens its own confirmation dialog and the server checks role and current status.
- **Suspend** (active approved accounts): one or more reasons are required (`reasons[]`), details up to 500 characters (required for "Other"); sets `account_status = suspended` with `suspended_at` and either a selected 1–365 day end date or no end date until lifted. The profile shows the reason, start, end, and a live countdown for timed suspensions.
- **Deactivate** (active approved accounts): sets `account_status = deactivated`.
- **Activate / Lift suspension** (suspended or deactivated approved accounts): sets `account_status = active`; for logistics centers it re-runs routing of unresolved ready orders.
- The Buyer login and authenticated Buyer routes now check `account_status` and archived state, so a suspended or deactivated Buyer cannot keep using an existing session. Seller and Logistics routes already have active-account middleware.
- After an action the page reloads and shows a result dialog that closes itself after five seconds.

Unsubmitted suspension reasons, duration and details survive a same-tab reload for up to two hours. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

New logistics-center approval remains in Admin Registrations. Rider and admin accounts are outside this screen.

## Backend update and remaining work

The count cards now count only approved Buyer, Seller and Logistics Center accounts, including their active, suspended and deactivated account states, matching the default list. Suspension validates the selected duration or permanent choice and enforces details for "Other" on the server. Suspend, deactivate and reactivate actions write actor, target, action, reason and time to `admin_action_logs` in the same database transaction as the account update. The new audit table needs migration `2026_10_04_000003_create_admin_action_logs_table.php`.

The browser still fetches the full index page for in-place refresh; the existing table endpoint does not include dialogs. A dedicated table-plus-dialogs response is optional. Rejected counts are not a separate stat card. Owner verification is pending.

## Source evidence

`app/Http/Controllers/Admin/UserManagementController.php`, `resources/views/admin/user-management/`, `resources/css/admin/registrations.css`, shared ID partial `resources/views/admin/registrations/partials/id-preview.blade.php`, and the shared popup `resources/views/admin/partials/document-viewer.blade.php` (`id-lightbox` is no longer used).

## Related documentation

See [Registration Review](../manage-account-registration/spec.md), [Admin document viewer](../document-viewer/spec.md), [domain status](../../../domain-feature-status.md), and the [Admin domain page](../../../domains/Admin.md).