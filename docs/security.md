# Vendo Web and API Security

**Reviewed:** 28 September 2026  
**Scope:** Laravel web application and the Rider API in this repository. This is a code-based security snapshot, not a penetration test or a production security certification.

## Trust boundaries

- Browser users authenticate through Laravel sessions and role guards. Blade forms use Laravel's web middleware and CSRF protection. Authorization must still be enforced by the route middleware, controller, policy, or scoped query for each operation.
- The Rider app is an untrusted API client. It uses HTTPS in production and a short-lived Sanctum bearer token; it never connects directly to MariaDB. Tracking numbers, client scan UUIDs, and IDs identify requested work but do not grant access.
- The server owns account approval, Main Hub membership, assignments, order status transitions, and scan events. Client-supplied role, center, rider, and destination values must not override those decisions.

## Controls present in code

### Browser identity and sessions

- Admin routes use the `admin` guard and active-account/password-change middleware where required. Buyer, Seller, and Logistics pages use authenticated sessions and role-specific active/approval gates.
- Login controllers regenerate the session after authentication; logout invalidates the session and rotates its CSRF token.
- Passwords are stored through Laravel password hashing. State-changing browser forms use CSRF protection. Sensitive operations have route-level throttles where implemented.
- Buyer, Seller, and Logistics Google login validates a signed Google ID token on the server. The verifier checks RS256 signature, issuer, expiry, verified email, and configured audience/authorized-party IDs. Google only satisfies email verification for new registration; it does not skip the role's profile or approval requirements.

### Rider API

- Public registration, login, OTP, Google exchange, password reset, and location endpoints have route throttles. OTPs are six digits, stored as hashes in cache for ten minutes, and verification returns a random, email-bound, expiring proof. Password reset returns a separate short-lived proof, uses a generic send response, changes the password under a row lock, and revokes existing Rider tokens.
- Rider password and Google login issue seven-day Sanctum tokens with the `rider:scan` ability. Protected endpoints also require `EnsureApprovedRiderToken`, which checks the Rider role, approval, active account, and current approved/active Main Hub membership on each request.
- Assignment queries are scoped to the authenticated Rider, assignment type, and linked hub. Truck assignments omit buyer contact/address details. Scan processing checks the assigned actor, hub, expected status, and scan UUID; it locks the order and records the transition and scan event transactionally. A tracking barcode/QR is not an authentication credential.
- Registration validates identity images by image type and 5 MB size limit, stores Rider verification files on the `local` private disk, and deletes saved files if database creation fails.

### Files, messages, and personal data

- Private message attachment endpoints require an authenticated participant/authorized admin lookup before serving the stored file. Rider verification files are not written to the public disk by the Rider API.
- Buyer, Seller, and Logistics identity and permit uploads now use the private `local` disk. Admin document previews use an authenticated, active Admin route that resolves only the selected user's allowed document field. Logistics Rider document links use a separate approved-center route and verify the Rider belongs to that center. Neither route accepts a filesystem path from the request.
- Existing files under public `valid-ids` and `business-permits` can be moved with `php artisan verification:privatize`; it checks that the private copy matches before removing the public source. The local run moved 11 files with no reported failure. Any previously copied or cached public URL cannot be revoked from other people's devices.
- Admin account provisioning currently stores a temporary password in `users.temp_password_plain` until the admin changes it, and can display that value again to the superuser. This is a sensitive design gap; remove plaintext persistence and use a one-time delivery/reset flow in a later code change.

## Configuration and deployment

- Keep `.env`, OAuth client secrets, mail credentials, signing keys, and production connection strings out of Git. Public Google client IDs are identifiers, not client secrets.
- Configure `APP_KEY`, production database/mail/cache settings, `APP_DEBUG=false`, HTTPS, and the correct public URL in the Azure environment. Deploy `GOOGLE_WEB_CLIENT_ID` and `GOOGLE_ALLOWED_CLIENT_IDS` for web and Rider ID-token audiences; register local and production web origins plus native app identities in Google Cloud.
- The production app is expected at `https://vendo-ph.app` behind Azure and Cloudflare Tunnel. Verify Laravel's trusted proxy and secure-cookie settings against the actual tunnel headers before release; this document does not certify that deployment configuration.
- Private files on the local disk are only as protected as the host filesystem and backups. Confirm filesystem permissions, backup access, and storage encryption in the actual Azure setup.
- Keep `storage/app/private` on persistent storage across Azure deployments and include it in access-controlled backups; the database stores relative document paths, not the file contents.

## Open security work

1. Stop persisting admin temporary passwords in plaintext; rotate any still-active temporary credentials as part of the migration.
2. Run `php artisan verification:privatize` in any environment with legacy public registration uploads before expecting their private previews to work. Review its reported failure count and the old public URLs.
3. Verify rate-limit/cache behavior, HTTPS/proxy settings, cookie flags, and secret handling in the deployed Azure/Cloudflare environment.
4. Perform owner-led authorization and security review for every private resource route, then arrange an independent security assessment before production scale-up.

## Source references

`routes/web.php`, `routes/api.php`, `app/Http/Controllers/Api/`, `app/Http/Middleware/EnsureApprovedRiderToken.php`, `app/Services/RiderAccessService.php`, `app/Services/GoogleIdTokenVerifier.php`, `app/Services/OrderScanWorkflow.php`, and `config/filesystems.php`.

Related behavior is documented in [architecture](architecture.md), [Google sign-in](features/shared/google-auth/spec.md), [Rider scan API](features/courier/scan-api/spec.md), and [schema](schema.md).
