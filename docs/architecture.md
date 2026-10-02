# Current Architecture

## Runtime shape

Vendo is a server-rendered Laravel application. `routes/web.php` defines public pages and role-area routes; controllers coordinate request validation and Eloquent queries; Blade views render the role-specific pages. Vite builds CSS and JavaScript under `resources/`. The existing buyer and admin pages are primarily Blade; the seller Orders screen also uses JavaScript to refresh rows and load an order drawer.
The public Terms and Privacy routes render repository Markdown through a shared legal Blade page. Buyer, Seller, and Logistics Center web registration forms link to those routes; their required checkbox does not yet store a versioned acceptance record.

## Main domains

- **Identity:** one `users` table with role/status/account-state fields; role-specific data is held in profile tables. Separate admin authentication guard exists.
- **Authentication:** Buyer, Seller, and Logistics browser login support password sessions and Google Identity Services. Laravel validates Google ID-token signatures and claims against cached Google JWKS and configured OAuth client IDs. New Google identities continue through their normal registration form with a short-lived role/email proof; Google does not bypass profile requirements or account approval. Rider mobile supports email OTP registration, password/Google login, and email-code password reset through `/api/v1/rider`. Google JWT verification uses `firebase/php-jwt`; Firebase Auth or database services are not used.
- **Commerce:** categories, products/images, cart items, orders/items, and order status events. Checkout accepts selected buyer-owned cart lines, creates one order per seller represented in that selection, and leaves unselected lines in the cart.
- **Operations:** logistics-center and Rider profile records; the protected center portal reviews Rider applicants. Seller readiness selects origin and destination Main Hubs by city/province. Origin assigns pickup and records arrival/sorting; origin sorting selects among configured ordered virtual SH locality route plans. Origin Logistics can assign an approved Truck Rider to a sorted cross-hub order, and the Rider API exposes that assigned route read-only. The rider API still has legacy SOC-coded inter-hub scan states. Destination Main Hub confirms receipt and assigns delivery. The rider API records pickup, origin arrival, legacy inter-hub states, out-for-delivery and delivered scans. Actual SH locality scans, truck movement/manifests, custody handoffs, and location-based final-mile routing are not implemented. Rider, Truck, and SH scanner interfaces are mobile-only; Laravel provides the protected backend/API and Logistics Center portal, not their dashboards. Independent delivery proof and exceptions remain future work.
- **Platform operations:** notifications, announcements, platform policies, conversations/messages/attachments, complaints/evidence/activity, compliance warnings/violations, commission settings, and admin login-session records.

## Request flow

Browser → named web route → middleware/guard → controller validation and actor-scoped query → transaction/model changes → Blade or JSON partial response → browser update. Google browser credentials are sent to Laravel and verified server-side; the browser never decides that an email is verified. The separate Flutter rider client calls `/api/v1/rider` JSON routes. Public registration requires a server-issued email OTP or Google proof bound to the normalized email, validates PSGC residence, and matches a unique approved, active center; approved login issues seven-day, `rider:scan` Sanctum bearer tokens. The API checks approval, active account and center membership, and order assignment on every scan; the client itself is in the separate E-Comm_Mobile repository.

## Key boundaries

- `users.role` identifies buyer, seller, logistics center, courier, or admin. The database role enum was widened to include logistics center.
- Registration status and account status are different concepts. Current registration state includes pending/approved/disapproved; administrative state also includes suspension/deactivation fields.
- Orders are shared across buyer/seller and have a courier foreign key, but there is not yet a complete shipment/assignment aggregate.
- Communications and notifications are reusable tables/models, but no push-notification delivery service is documented as implemented.

## Current constraints

Most functionality is in the web route/controller layer. The mobile Rider API provides location choices, registration, vehicle-aware token login, assigned pickup/delivery work, read-only assigned Truck routes, existing rider scan transitions, and logout. It does not yet provide SH locality scanner workflow or truck movement scans. Rider, Truck, and SH scanner interfaces are mobile-only; the web app has no Rider dashboards. Logistics Messages remains a placeholder; center-scoped Delivery Monitoring and 30-day Reports are read-only order summaries. Some role routes need a security review. See the [status matrix](domain-feature-status.md), [registration contract](features/courier/registration-api/spec.md), and [rider scan contract](features/courier/scan-api/spec.md).

Production web domain: `vendo-ph.app`; hosting is Azure behind Cloudflare Tunnel. Set `APP_URL=https://vendo-ph.app`, `SESSION_SECURE_COOKIE=true`, and `TRUSTED_PROXIES` to the actual comma-separated reverse-proxy IPs/CIDRs that connect to Laravel. Rebuild cached config during deployment. The location catalog is bundled, so province/city checkout and routing need no runtime PSGC request. Rider registration also uses a bundled barangay snapshot through the versioned API; the older web registration forms still fetch barangays from the external PSGC mirror.

Google sign-in additionally requires `GOOGLE_WEB_CLIENT_ID` and `GOOGLE_ALLOWED_CLIENT_IDS` in Laravel's deployment environment. Configure the deployed origin and local development origins in the Google OAuth project; keep OAuth client secrets out of source control. Details and the native app setup are in the [Google sign-in spec](features/shared/google-auth/spec.md).
