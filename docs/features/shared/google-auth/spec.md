# Google sign-in

**Status:** Server verification and Buyer/Seller/Logistics browser login implemented; Rider API exchange implemented. Owner OAuth configuration and acceptance verification pending.
**Updated:** 28 September 2026

## Behavior

Buyer, Seller, and Logistics login pages render the official Google Identity Services button in English, independent of browser/account locale. The browser posts Google's ID token to `POST /auth/google`; Laravel validates its signature with Google's cached JWKS, issuer, expiry, verified email, audience, and authorized party against configured client IDs. Invalid or unconfigured credentials are rejected. A matching existing account signs in only under its own role and after active/approval/profile checks. A new identity receives a short-lived role-bound session proof and is redirected to that role's existing registration form. The Google email is prefilled and locked; all normal identity/profile/business documents, passwords, residence checks, and approval requirements remain.

The Rider mobile app uses `POST /api/v1/rider/google`. Existing approved active Rider accounts linked to an approved active Main Hub receive the normal seven-day rider bearer token. New identities receive a short-lived one-use registration proof and complete the regular Rider registration form; the proof satisfies email verification only. Rider applications remain pending for Logistics Center approval.

Google authentication does not create a Firebase project or use Firebase Auth/Firestore/Realtime Database. `firebase/php-jwt` is used only to verify JWT signatures and claims. Web clients render Google's official button. The Flutter Rider integration uses `google_sign_in` on Android/iOS; the plugin does not support Linux desktop.

## Configuration

Set `GOOGLE_WEB_CLIENT_ID` to the Google OAuth Web client ID used by GIS and by the mobile app as its server client ID. Set `GOOGLE_ALLOWED_CLIENT_IDS` to a comma-separated list of every accepted OAuth client ID; it defaults to the web client ID. The Rider Android package name and signing SHA fingerprints, iOS bundle/client ID plus reversed client URL scheme, and macOS bundle/client ID plus callback URL scheme must be registered in the same Google Cloud OAuth project. Do not commit client secrets. Public client IDs are not secrets, but must be registered accurately. Production website origin and authorized redirect/origin configuration must include `https://vendo-ph.app`.

## Source evidence and limitations

`app/Services/GoogleIdTokenVerifier.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `app/Http/Controllers/Api/RiderGoogleAuthController.php`, `resources/js/auth/google-signin.js`, and `vendo_rider/lib/features/auth/screens/login_screen.dart`. Google signing keys are cached for six hours and refreshed for an unknown key ID. Cache availability and correct OAuth client configuration are required. No database migration is required. Owner browser, Android/iOS, OAuth-console, and deployed-domain verification remain pending.
