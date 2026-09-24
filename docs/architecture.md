# Current Architecture

## Runtime shape

Vendo is a server-rendered Laravel application. `routes/web.php` defines public pages and role-area routes; controllers coordinate request validation and Eloquent queries; Blade views render the role-specific pages. Vite builds CSS and JavaScript under `resources/`. The existing buyer and admin pages are primarily Blade; the seller Orders screen also uses JavaScript to refresh rows and load an order drawer.

## Main domains

- **Identity:** one `users` table with role/status/account-state fields; role-specific data is held in profile tables. Separate admin authentication guard exists.
- **Commerce:** categories, products/images, cart items, orders/items, and order status events. Checkout creates one order per seller for the buyer's cart.
- **Operations:** logistics-center and courier profile records; current center dashboard reviews rider applicants. Full dispatch and delivery action services are not present.
- **Platform operations:** notifications, announcements, platform policies, conversations/messages/attachments, complaints/evidence/activity, compliance warnings/violations, commission settings, and admin login-session records.

## Request flow

Browser → named web route → middleware/guard → controller validation and actor-scoped query → transaction/model changes → Blade or JSON partial response → browser update. Current mobile integration is not implemented; a future mobile client needs a versioned JSON API and token/session strategy.

## Key boundaries

- `users.role` identifies buyer, seller, logistics center, courier, or admin. The database role enum was widened to include logistics center.
- Registration status and account status are different concepts. Current registration state includes pending/approved/disapproved; administrative state also includes suspension/deactivation fields.
- Orders are shared across buyer/seller and have a courier foreign key, but there is not yet a complete shipment/assignment aggregate.
- Communications and notifications are reusable tables/models, but no push-notification delivery service is documented as implemented.

## Current constraints

Most functionality is in the web route/controller layer, not a documented API. Some role routes need a security review, and some menu links are visual placeholders. See the [status matrix](domain-feature-status.md).
