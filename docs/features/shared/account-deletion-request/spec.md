# Account deletion request (Buyer and Seller)

**Status:** Implemented in code; migration `2026_11_11_100000_create_account_deletion_requests_table.php` must be run by the owner; owner verification pending
**Reviewed:** 11 November 2026

## Current behavior

A Buyer or Seller can ask Vendo to close their account from their own profile. The request is stored, not acted on immediately: an Admin reviews it and decides.

**Requesting (Buyer).** Buyer Account → Settings → *Privacy and terms* now has a **Delete account** card. **Request deletion** opens a dialog with one required reason (`reasons[]`, radio) — *No longer using Vendo*, *Privacy concerns*, *Too many emails or notifications*, *Found another marketplace*, *Created the account by mistake*, *Other* — and an optional comment (`comments`, max 500). Picking *Other* requires the comment, enforced on the server. The form posts to `buyer.account.deletion-request` and returns to the account page with a success banner. While a request is pending the card shows when it was sent and the button is disabled, so only one request can wait at a time.

**Requesting (Seller).** Seller Account → *Security* tab has the same **Delete account** card and dialog, posting to `seller.account.deletion-request`. The native `<dialog>` closes on *Keep my account*, on the × button, on Escape, or on a click outside. Validation errors reopen it with the message under the field.

The account stays fully usable while the request is pending. Nothing is deleted at request time.

**Reviewing (Admin).** Admin → User Management → open a Buyer or Seller profile. Under **Suspend** / **Deactivate** (or the Activate button) there is a new bar, **User request for deletion of account**. It shows a *Pending* pill when a request is waiting. Opening it:
- with a request: reason chips, the comment (or “No comment added.”), requested date, and, after a decision, the reviewer, review time and Admin note;
- without a request: an empty panel (“No deletion request”).

While the request is pending the panel offers:
- **Approve deletion (closes the account)** — marks the request `approved` and sets `account_status = deactivated`, so the user can no longer sign in. Orders, products, messages and other records are kept.
- **Reject request** — marks it `rejected`, optionally with an Admin note (max 500 characters). The account is unchanged.

Both decisions run in a transaction with an `admin_action_logs` row (`deletion_approved` / `deletion_rejected`) and show the shared confirmation banner.

## Data

`account_deletion_requests`: `user_id` (cascade), `reasons` (JSON), `comments`, `status` (`pending`/`approved`/`rejected`), `requested_at`, `reviewed_by` (Admin user, null on delete), `reviewed_at`, `admin_notes`, timestamps; index on `(user_id, status)`. `User` exposes `deletionRequests`, `latestDeletionRequest`, and `pendingDeletionRequest`.

## Gaps and acceptance direction

- Approving closes access by deactivating the account; it does **not** hard-delete the row or anonymize personal data. Order history, reviews and messages still reference the user. A later retention/anonymization job would be needed for true erasure.
- No email or Admin notification is sent when a request arrives; the Admin sees it only in User Management. No badge or filter exists on the list for pending deletions.
- Only Buyer and Seller can request. Logistics centers, Riders and Drivers are out of scope.
- A pending request does not block checkout, listing products, or suspensions and other Admin actions taken in the meantime.

## Source evidence

`app/Models/AccountDeletionRequest.php`, `app/Models/User.php`, `app/Http/Controllers/Buyer/AccountController.php`, `app/Http/Controllers/Seller/AccountController.php`, `app/Http/Controllers/Admin/UserManagementController.php`, `resources/views/buyer/account.blade.php`, `resources/views/seller/account/index.blade.php`, `resources/views/admin/user-management/partials/deletion-request-modal.blade.php`, `resources/views/admin/user-management/partials/profile-modal.blade.php`, `routes/web.php`.

## Related documentation

See [Buyer account](../../buyer/account-management/spec.md), [Seller account management](../../seller/account-management/spec.md), [User management](../../admin/manage-user-accounts/spec.md), and the [11 November progress log](../../../logs/PROGRESS-2026-11-11.md).
