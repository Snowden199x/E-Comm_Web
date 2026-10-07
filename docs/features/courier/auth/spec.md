# Courier Authentication

**Status:** Mobile password/Google login, logout, email OTP registration, and password reset API implemented; owner verification pending
**Reviewed:** 28 September 2026

## Current behavior

`CourierDetail` exists, and the mobile API authenticates approved active riders with a versioned bearer token. Password login and Google ID-token exchange both require an approved, active rider linked to an approved, active logistics center. New Google identities receive a short-lived one-use registration proof and continue through the normal mobile Rider form; Google does not bypass Logistics Center approval. Email OTP proof is also required for mobile email/password registration. Password reset uses an email code and one-use reset proof, then revokes the rider's existing API tokens. The Logistics Center web portal owns rider application approval; the rider signs in and works through the separate mobile app. Courier, rider, and truck operators do not have web dashboards.

## Gaps and acceptance direction

The shared browser login still contains a legacy courier redirect to an undefined dashboard route. Browser login is not the supported courier workflow; remove or block that legacy path when the web auth code is next changed. Device management remains future work. Google Sign-In requires configured OAuth client IDs; the Flutter Google plugin supports Android/iOS/macOS, not Linux desktop.

## Source evidence

`app/Http/Controllers/Api/RiderAuthController.php`, `app/Http/Controllers/Api/RiderGoogleAuthController.php`, `app/Http/Controllers/Api/RiderEmailOtpController.php`, `app/Http/Controllers/Api/RiderPasswordResetController.php`, `routes/api.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
