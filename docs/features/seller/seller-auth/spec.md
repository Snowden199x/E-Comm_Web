# Seller Registration and Access

**Status:** Email OTP and Google verified registration implemented; owner verification pending
**Reviewed:** 28 September 2026

## Current behavior

Seller submits identity, address, business permit, categories, and password after email OTP verification or server-verified Google sign-in. A Google sign-in for a new email continues into the same registration form with the verified email locked; it does not bypass required profile/business documents or admin approval. Existing Google accounts sign in only under their matching role and only after approval. The server verifies Google ID tokens against Google's signing keys and configured OAuth client IDs. Account remains pending until admin approval; active approved seller middleware guards dashboard/orders.
The valid ID and business permit now upload to private local storage. Admin previews use an authenticated, active Admin route; the legacy public files can be moved with `php artisan verification:privatize` while retaining their saved relative paths.
The shared registration view initializes email and Google verification values before rendering the script that uses them, so opening the form does not depend on a Google session.
The Business Information step loads top-level categories from the database. After creating a fresh database with `php artisan migrate`, run `php artisan db:seed --class=CategorySeeder` to populate the choices; migration alone leaves the list empty. The current form presents these as multiple-selection checkboxes.
An unfinished Seller registration restores ordinary fields and address choices after a reload in the same tab for up to two hours. The form returns to step one and prompts for passwords and document uploads again. OTP/Google verification still follows the server session; browser storage cannot verify an email. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Align cache OTP/session behavior with buyer registration, store uploads transactionally, and test role/status edges.

## Source evidence

`app/Http/Controllers/Seller/RegisteredUserController.php`, `app/Http/Middleware/EnsureActiveSeller.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
