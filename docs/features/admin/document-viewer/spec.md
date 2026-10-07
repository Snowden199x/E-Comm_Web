# Admin Document Viewer

**Status:** Implemented (added 6 October 2026; owner verification pending)  
**Reviewed:** 6 October 2026

## Current behavior

Admin screens that show private verification files open them in one shared popup instead of a new tab. The popup is rendered once by `components/admin/layout.blade.php`, so it is available on every Admin page and survives in-page refreshes.

**Opening a document.** Any Admin view dispatches `open-document` with `{ url, title, subtitle, filename, kind }` (`kind` is `image` or `pdf` and is only a hint when the server sends no usable type). Current callers: the ID thumbnail and file chips in `registrations/partials/id-preview.blade.php`, and the business-permit button in `registrations/partials/applicant-details.blade.php` and `user-management/partials/profile-modal.blade.php`. These cover Registrations details, User Management profile, and the Dashboard "Recent Registrations" dialog.

**Loading.** The popup fetches `url` with the admin session (the existing `admin.verification-documents.show` route) and reads the response `Content-Type`:

| Result | Shown |
|---|---|
| `image/*` | Image, fitted to the window, zoom 50–400% (buttons, `+`, `-`, `0`) |
| `application/pdf` | Browser PDF viewer in an inline frame |
| `text/html` (login redirect) | "Session expired" |
| 404 | "File not found" (not in private storage) |
| 403 | "Access denied" |
| 5xx or network failure | Message with "Try again" |
| any other type | "Unsupported file" |
| image that cannot be decoded | "The file could not be displayed" |

The file is held as an in-memory blob URL that is revoked shortly after the popup closes. The popup has no download, print, or open-in-new-tab control.

**Accessibility.** `role="dialog"` with `aria-modal`, focus moves to Close on open and returns to the trigger on close, Tab is trapped, and Esc closes only the popup (it runs in the capture phase so a profile or registration dialog behind it stays open). Transitions are removed for `prefers-reduced-motion`.

**Thumbnail rules (`id-preview`).** Image IDs show a thumbnail; PDF IDs show a document tile; a thumbnail that fails to load shows "Preview couldn't load". The thumbnail and chips always stay clickable so the admin can see the specific reason in the popup. A Buyer's second ID appears as a chip.

## Gaps and acceptance direction

- Authorization is unchanged and stays with the server route; hiding or showing the popup is not access control.
- Mobile browsers that cannot render PDFs inline show a blank frame. The Admin portal is a desktop workflow.
- Viewing a document is not recorded. See [Admin backend needs](../../../design/2026-10-06-admin-backend-needs.md) if the owner wants an audit trail.
- Files missing from private storage need `php artisan verification:privatize` (legacy public uploads) or a re-upload; the viewer only reports the problem.
- Verified by static review only. No automated tests or browser walkthrough were run.

## Source evidence

`resources/views/admin/partials/document-viewer.blade.php`, `resources/js/admin/document-viewer.js`, `resources/views/components/admin/layout.blade.php`, `app/Http/Controllers/VerificationDocumentController.php`

## Related documentation

See [Registration Review](../manage-account-registration/spec.md), [User Management](../manage-user-accounts/spec.md), [security overview](../../../security.md), and [domain status](../../../domain-feature-status.md).