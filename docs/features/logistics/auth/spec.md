# Logistics Center Authentication

**Status:** Email OTP / Google registration and Company approval implemented; Company → Province Hub → Station Hub portal foundation is partial
**Reviewed:** 23 October 2026

## Current behavior

Company registration uses email OTP or server-verified Google sign-in, saves business/profile documents, and awaits Admin approval. Registration creates the existing Logistics Center profile and a pending Company record; it does not reserve a province or create a hub. Admin approval/rejection updates both the user and Company status. A new Google identity continues to the regular registration form with its verified email locked; Google does not bypass business/profile documents or Admin approval. Existing Google accounts sign in only under their matching role after approval. Company, Province Hub and Station Hub logins enter their scoped `/logistics/portal/*` dashboard after approval and active-account checks.
The address on Company registration is its office address. A Company Admin creates Province Hubs after approval, then Province Hub managers create Station Hubs. Multiple companies can serve the same province; when address matching finds overlapping active stations, the matcher leaves it unresolved instead of selecting an arbitrary company. A backfill migration associates each existing Logistics Center with a Company and remaps its old Main Hub/station facilities; it does not delete prior rows.
The center operator ID and business permit now upload to private local storage and use an active Admin route for review. Rider IDs, licenses, and OR/CR images were already private; Rider Management now opens them through a protected Logistics route that checks center ownership.
The shared registration view initializes email and Google verification values before rendering the script that uses them, so opening the form does not depend on a Google session.
An unfinished Logistics Center registration restores ordinary fields and address choices after a reload in the same tab for up to two hours. It returns to step one so passwords and document uploads can be entered again; server OTP/Google proof remains required. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Transport Driver onboarding and the new hierarchy's station scanning/task workflows remain future work. The Rider mobile app remains in the separate E-Comm_Mobile repository.

## Source evidence

`app/Http/Controllers/Logistics/Auth/`, `routes/web.php`

## Related documentation

See [province network and stations](../province-network-and-stations/spec.md) for Company/Province Hub/Station Hub ownership, [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
