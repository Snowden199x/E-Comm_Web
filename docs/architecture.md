# Current Architecture

## Runtime shape

Vendo is a server-rendered Laravel application. `routes/web.php` defines public pages and role-area routes; controllers coordinate request validation and Eloquent queries; Blade views render the role-specific pages. Vite builds CSS and JavaScript under `resources/`. The existing buyer and admin pages are primarily Blade; the seller Orders screen also uses JavaScript to refresh rows and load an order drawer.

## Main domains

- **Identity:** one `users` table with role/status/account-state fields; role-specific data is held in profile tables. Separate admin authentication guard exists.
- **Commerce:** categories, products/images, cart items, orders/items, and order status events. Checkout accepts selected buyer-owned cart lines, creates one order per seller represented in that selection, and leaves unselected lines in the cart.
- **Operations:** logistics-center and courier profile records; the protected center portal reviews rider applicants. Seller readiness selects origin and destination centers by city/province. Origin assigns pickup, records arrival/sorting and sends to destination; destination confirms receipt and assigns delivery. A versioned rider API now records assigned pickup, origin-arrival and out-for-delivery scans. Delivery completion remains future work.
- **Platform operations:** notifications, announcements, platform policies, conversations/messages/attachments, complaints/evidence/activity, compliance warnings/violations, commission settings, and admin login-session records.

## Request flow

Browser → named web route → middleware/guard → controller validation and actor-scoped query → transaction/model changes → Blade or JSON partial response → browser update. The separate future rider client can call `/api/v1/rider` JSON routes with seven-day, `rider:scan` Sanctum bearer tokens. The API checks approval, active account and center membership, and order assignment on every scan; the client itself is not in this repository.

## Key boundaries

- `users.role` identifies buyer, seller, logistics center, courier, or admin. The database role enum was widened to include logistics center.
- Registration status and account status are different concepts. Current registration state includes pending/approved/disapproved; administrative state also includes suspension/deactivation fields.
- Orders are shared across buyer/seller and have a courier foreign key, but there is not yet a complete shipment/assignment aggregate.
- Communications and notifications are reusable tables/models, but no push-notification delivery service is documented as implemented.

## Current constraints

Most functionality is in the web route/controller layer. The new rider API is limited to login, assigned-work listing, three scan transitions, and logout. Some role routes need a security review, and some Logistics sidebar links are visual placeholders. See the [status matrix](domain-feature-status.md) and [rider scan contract](features/courier/scan-api/spec.md).

Production web domain: `vendo-ph.app`; hosting is Azure behind Cloudflare Tunnel. Set `APP_URL=https://vendo-ph.app`, `SESSION_SECURE_COOKIE=true`, and `TRUSTED_PROXIES` to the actual comma-separated reverse-proxy IPs/CIDRs that connect to Laravel. Rebuild cached config during deployment. The location catalog is bundled, so province/city checkout and routing need no runtime PSGC request; registration barangay choices still use the external PSGC mirror.
