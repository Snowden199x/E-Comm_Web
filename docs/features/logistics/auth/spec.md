# Logistics Center Authentication

**Status:** Email OTP and Google verified registration implemented; owner verification pending
**Reviewed:** 28 September 2026

## Current behavior

Center registration uses email OTP or server-verified Google sign-in, saves business/profile documents, and awaits admin approval. Successful registration creates a platform notification for Admin that opens the Logistics Center registrations filter. A new Google identity continues to the regular registration form with its verified email locked; Google does not bypass the business/profile documents or admin approval. Existing Google accounts sign in only under their matching role after approval. The dashboard, account page, and rider review actions require an authenticated, approved, active logistics-center account with a center profile. Shared logistics login accepts approved center/courier roles.
The center operator ID and business permit now upload to private local storage and use an active Admin route for review. Rider IDs, licenses, and OR/CR images were already private; Rider Management now opens them through a protected Logistics route that checks center ownership.
The shared registration view initializes email and Google verification values before rendering the script that uses them, so opening the form does not depend on a Google session.
An unfinished Logistics Center registration restores ordinary fields and address choices after a reload in the same tab for up to two hours. It returns to step one so passwords and document uploads can be entered again; server OTP/Google proof remains required. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Courier login currently redirects to an undefined named route. The rider app and its authentication contract remain future work.

## Source evidence

`app/Http/Controllers/Logistics/Auth/`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
