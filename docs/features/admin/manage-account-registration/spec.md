# Registration Review

**Status:** Implemented (UI redesigned 4 October 2026; file popups added 6 October 2026; owner verification pending)  
**Reviewed:** 7 October 2026

## Current behavior

Admin reviews pending buyer, seller, and logistics-center applications. Riders are not reviewed here; the linked Logistics Center approves them.

**List (`admin.registrations.index`, partial `admin.registrations.table`).** Pending-count cards for all requests, sellers, buyers, and logistics centers; selecting a card filters the table to that user type. The cards use the shared `admin/partials/filter-stat-cards.blade.php` (white card with a border, tinted round icon chip, staggered rise; the active card has a plum border and selecting it again clears the filter). Search by name or email and a date filter refresh the table in place; the separate user-type dropdown was removed because the cards do the same job (debounced, stale requests cancelled, URL kept in sync). Eight rows per page with a windowed page list; page buttons use the same fetch, so pagination never navigates to the table partial. Rows have a "Review" button and the whole row is clickable. An empty result uses the shared empty state ("No matches" with a "Clear filters" button when filtered; "No pending registrations" otherwise). A failed refresh shows an error with the fix and a "Try again" button; the table dims with a progress bar while loading. The counts always show all pending applications and do not change with filters.

**Details (`admin.registrations.show`).** A profile card (name, role, contact, business name for sellers and logistics centers, date applied, status) beside tabs: Personal information, Address, and, for sellers and logistics centers, Business information. The page has Reject and Approve buttons (pastel danger and success, with a border) while the application is pending, and a status chip afterwards. Approve runs directly and shows a spinner; the result appears in the shared flash banner. Street and house number display as one "Street / House No." line. A submitted valid ID shows as a thumbnail (a document tile for a PDF) that opens the shared document popup; a failed preview shows "Preview didn't load" and the popup explains why. Business permits, IDs, and a Buyer's second ID open through the protected Admin document route in that popup, never in a new tab. See [Admin document viewer](../document-viewer/spec.md).
The Logistics Business Information tab shows the submitted company details and office address; province coverage is not claimed at registration. A pending Company record is created with the Logistics Center profile, and approval/rejection updates both statuses. Companies create their Province Hubs after approval.

**Decisions.** While an application is pending, a sticky bar offers Reject and Approve.
- **Approve** asks for confirmation, sets `status = approved`, updates the linked Logistics Company status, sends `AccountApprovedMail` in a guarded block, and for logistics centers re-runs routing of unresolved ready orders.
- **Reject** requires a reason (one of seven options) with optional details up to 500 characters, and stores `rejection_reason` and `rejection_notes` with `status = disapproved`. **No email is sent.** The applicant sees the reason the next time they try to sign in. The "Other" option requires details in the browser and on the server.
- After either decision the page shows a result dialog (auto-closes after five seconds) and the status badge in place of the buttons.

An unfinished rejection reason/details selection survives a same-tab reload for up to two hours. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

The Dashboard "Recent Registrations" dialogs reuse the applicant-details and reject-dialog partials, so their contracts (`$user`, `$showExpr`, `$closeExpr`) must be kept.

ID and business-permit previews use a protected Admin route that selects the applicant's stored document field and reads the private disk. Public file URLs are not used for these documents.

## Gaps and acceptance direction

- Approval/rejection now records the acting Admin in `admin_action_logs`. Approval changes the account inside a transaction, routes unresolved Logistics parcels, then sends email in a guarded block. A transport failure keeps approval and shows an explicit mail warning in the result dialog. Owner verification remains pending.
- Decide whether a rejection should send an email; if so, add a mail class and change the result-dialog copy. The UI must not claim that email was sent until this exists.
- The server requires details for "Other (please specify)" and checks that a rejection target is a pending Buyer, Seller or Logistics Center.
- The owner's mockup includes a Courier applicant with a Vehicle Information tab. This is **not** implemented: riders are approved by their linked Logistics Center, `approve` returns 403 for couriers, and the Admin document route has no driver's license or OR/CR fields. Define Admin's role for riders before building it.
- The pending-count cards now use stroke icons from `<x-admin.icon>` for consistency with Seller Compliance. The exported files in `public/assets/icons/registration/` (`pending-request-icon.svg` and the seller, buyer, and logistics variants) are no longer referenced by the cards and can be removed or reused.
- Verified by static review only (Alpine expressions and Blade directives). No automated tests or browser walkthrough have been run.

## Source evidence

`app/Http/Controllers/Admin/RegistrationController.php`, `app/Http/Controllers/VerificationDocumentController.php`, `resources/views/admin/registrations/`, `resources/views/admin/partials/document-viewer.blade.php`, `resources/css/admin/registrations.css`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, [User Management](../manage-user-accounts/spec.md), [Admin document viewer](../document-viewer/spec.md), and [feature implementation guide](../../../feature-implementation-guide.md).

**31 October 2026.** The reject dialog and its server messages were reworded and restyled (shared with the Dashboard). Approval-email failure now reads "The confirmation email didn't send. Tell the applicant to sign in." 

**31 October 2026 (Registrations pass).** The list and detail pages follow the Admin design system (`docs/design/admin-design-system.md`): palette tokens, borders instead of shadows, sentence-case copy, verb buttons. The result dialog (`confirmation-modal`) and the unused `approve-modal` were removed; the shared `x-admin.flash` banner now shows the result of approve and reject.
