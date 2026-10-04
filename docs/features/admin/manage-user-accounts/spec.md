# User Management

**Status:** Implemented for buyer, seller and logistics-center accounts (UI redesigned 4 October 2026; owner verification pending)  
**Reviewed:** 4 October 2026

## Current behavior

The Admin User Management list and counts cover buyer, seller, and logistics-center accounts. The list shows approved, suspended, and deactivated accounts; the "Rejected users" toggle switches it to rejected (`disapproved`) accounts. Pending applications stay in Admin Registrations.

**List (`admin.user-management.index`).** Count cards for all users, sellers, buyers, and logistics centers; selecting a card filters the list to that type. Search by name or email, a date filter, a user-type filter, and the Rejected toggle refresh the list **in place**: the browser fetches the index page and swaps only `#um-region`, which holds the table and every per-user dialog together, so the dialogs always match the visible rows. Open dialogs are closed on each refresh. Eight rows per page, a windowed page list, and the URL is kept in sync. Status pills read Active, Suspended, Deactivated, or Rejected. Selecting a row or a name opens that user's profile.

**Profile (modal only, never a separate page).** A left card shows the avatar initial, role, status, email, phone, business name (sellers and logistics centers), category chips colored by `Category::colors` (sellers), and Date Joined. Below it are the actions for the user's status. The right side stacks Personal Information (with the valid ID preview and enlarge viewer), Address, and, for sellers and logistics centers, Business Information. Street and house number display as one "Street / House No." line. IDs and permits open through the protected Admin document route.

**Actions.** Admin can suspend, deactivate, and reactivate these roles; each action opens its own confirmation dialog and the server checks role and current status.
- **Suspend** (approved accounts): one or more reasons are required (`reasons[]`), optional details up to 500 characters; sets `status = suspended` with `suspended_at` and `suspended_until = now + 7 days`. The profile shows the reason, start, end, and a live countdown. The "Other" reason requires details in the browser only.
- **Deactivate** (approved accounts): sets `status = deactivated`.
- **Activate / Lift suspension** (suspended or deactivated accounts): sets `status = approved`; for logistics centers it re-runs routing of unresolved ready orders.
- After an action the page reloads and shows a result dialog that closes itself after five seconds.

New logistics-center approval remains in Admin Registrations. Rider and admin accounts are outside this screen.

## Gaps and acceptance direction

- Account audit history remains open.
- The count cards include pending and rejected accounts, while the list excludes pending ones, so "Total Users" and the type counts can exceed the visible rows. Counting only approved, suspended, and deactivated accounts would match the list.
- Suspension duration is fixed at 7 days; there is no duration control.
- `admin.user-management.table` still exists but the screen no longer uses it. A table-plus-dialogs partial endpoint would be lighter than fetching the full index page.
- Count-card icons use existing assets in a tinted circle; the mockup's circle icons are not exported yet.
- Verified by static review only (Alpine expressions and Blade directives). No automated tests or browser walkthrough have been run.

## Source evidence

`app/Http/Controllers/Admin/UserManagementController.php`, `resources/views/admin/user-management/`, `resources/css/admin/registrations.css`, shared ID partials in `resources/views/admin/registrations/partials/` (`id-preview`, `id-lightbox`).

## Related documentation

See [Registration Review](../manage-account-registration/spec.md), [domain status](../../../domain-feature-status.md), and the [Admin domain page](../../../domains/Admin.md).