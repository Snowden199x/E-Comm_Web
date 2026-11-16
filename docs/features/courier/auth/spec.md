# Courier Authentication

**Status:** Mobile password/Google login, logout, email OTP registration, and password reset API implemented; owner verification pending
**Reviewed:** 16 November 2026

## Current behavior

`CourierDetail` exists, and the mobile API authenticates approved active riders with a versioned bearer token. Password login and Google ID-token exchange both require an approved, active rider linked to an approved, active logistics center. `POST /api/v1/rider/login` answers 401 (`Invalid email or password.`) only for a wrong email/password or a non-rider account, so the shared Rider/Driver sign-in form can still retry the driver endpoint; a correct password on an account that is pending, disapproved, archived, deactivated, or whose Station Hub/company is inactive answers 403 with the specific reason (e.g. *waiting for approval from Santa Cruz Station*). New Google identities receive a short-lived one-use registration proof and continue through the normal mobile Rider form; Google does not bypass Logistics Center approval. Email OTP proof is also required for mobile email/password registration. Password reset uses an email code and one-use reset proof, then revokes the rider's existing API tokens. The Logistics Center web portal owns rider application approval; the rider signs in and works through the separate mobile app. Courier, rider, and truck operators do not have web dashboards. The `/logistics/login` web form now rejects `courier` and `transport_driver` accounts with a mobile-app message and does not issue a web session; the old redirect to the undefined `logistics.courier.dashboard` route was removed.

## Gaps and acceptance direction

Browser login is not the supported courier workflow. Device management remains future work. Google Sign-In requires configured OAuth client IDs; the Flutter Google plugin supports Android/iOS/macOS, not Linux desktop.

## Source evidence

`app/Http/Controllers/Api/RiderAuthController.php`, `app/Http/Controllers/Api/RiderGoogleAuthController.php`, `app/Http/Controllers/Api/RiderEmailOtpController.php`, `app/Http/Controllers/Api/RiderPasswordResetController.php`, `app/Http/Controllers/Logistics/Auth/AuthenticatedSessionController.php`, `routes/api.php`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
