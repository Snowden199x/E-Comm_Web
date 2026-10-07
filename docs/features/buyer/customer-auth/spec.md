# Buyer Authentication

**Status:** Password and Google login implemented; new Google registrations continue through buyer registration
**Reviewed:** 28 September 2026

## Current behavior

Buyer login/logout and shared password reset routes exist; buyer protected pages require authentication. The login page also accepts Google Identity Services credentials, which the server validates using Google's signing keys and configured OAuth client IDs. Existing users can sign in only through the matching role and approval gate. A new Google identity receives a short-lived session proof and continues through the normal Buyer form with the verified email locked; buyer identity documents and account review remain required.
New Buyer ID uploads use the private local disk; the Admin registration and user-management views read them through an authorized route. Legacy public IDs can be moved without changing their saved relative database paths using `php artisan verification:privatize`.
Unfinished registration fields and address selections survive a same-tab reload for up to two hours. The form returns to step one so passwords and ID files can be entered again; OTP/Google proof remains server controlled. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Verify role checks on every buyer route, since `auth` alone is not the same as buyer-role authorization.

## Source evidence

`app/Http/Controllers/Buyer/AuthenticatedSessionController.php`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
