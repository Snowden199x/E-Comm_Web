# Registration Review

**Status:** Implemented (UI redesigned 4 October 2026; owner verification pending)  
**Reviewed:** 4 October 2026

## Current behavior

Admin reviews pending buyer, seller, and logistics-center applications. Riders are not reviewed here; the linked Logistics Center approves them.

**List (`admin.registrations.index`, partial `admin.registrations.table`).** Pending-count cards for all requests, sellers, buyers, and logistics centers; selecting a card filters the table to that user type. Search by name or email, a date filter, and a user-type filter refresh the table in place (debounced, stale requests cancelled, URL kept in sync). Eight rows per page with a windowed page list; page buttons use the same fetch, so pagination never navigates to the table partial. An empty result offers "Clear filters". The counts always show all pending applications and do not change with filters.

**Details (`admin.registrations.show`).** A profile card (name, role, contact, business name for sellers and logistics centers, date applied, status) beside tabs: Personal Information, User Address, and, for sellers and logistics centers, Business Information. Street and house number display as one "Street / House No." line. A submitted valid ID shows as a thumbnail that opens a larger viewer; a failed preview falls back to a link. Business permits, IDs, and a Buyer's second ID open through the protected Admin document route.

**Decisions.** While an application is pending, a sticky bar offers Reject and Approve.
- **Approve** asks for confirmation, sets `status = approved`, sends `AccountApprovedMail` synchronously, and for logistics centers re-runs routing of unresolved ready orders.
- **Reject** requires a reason (one of seven options) with optional details up to 500 characters, and stores `rejection_reason` and `rejection_notes` with `status = disapproved`. **No email is sent.** The applicant sees the reason the next time they try to sign in. The "Other" option requires details in the browser only; the server treats details as optional.
- After either decision the page shows a result dialog (auto-closes after five seconds) and the status badge in place of the buttons.

The Dashboard "Recent Registrations" dialogs reuse the applicant-details and reject-dialog partials, so their contracts (`$user`, `$showExpr`, `$closeExpr`) must be kept.

ID and business-permit previews use a protected Admin route that selects the applicant's stored document field and reads the private disk. Public file URLs are not used for these documents.

## Gaps and acceptance direction

- Add action audit events, verify details before mail/send failure handling (approval is saved before the email is sent), and test each account role.
- Decide whether a rejection should send an email; if so, add a mail class and change the result-dialog copy.
- The owner's mockup includes a Courier applicant with a Vehicle Information tab. This is **not** implemented: riders are approved by their linked Logistics Center, `approve` returns 403 for couriers, and the Admin document route has no driver's license or OR/CR fields. Define Admin's role for riders before building it.
- Pending-count icons `pending-*-icon.svg` are expected in `public/assets/icons/registration/`; older icons are used until they exist.
- Verified by static review only (Alpine expressions and Blade directives). No automated tests or browser walkthrough have been run.

## Source evidence

`app/Http/Controllers/Admin/RegistrationController.php`, `app/Http/Controllers/VerificationDocumentController.php`, `resources/views/admin/registrations/`, `resources/css/admin/registrations.css`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, [User Management](../manage-user-accounts/spec.md), and [feature implementation guide](../../../feature-implementation-guide.md).