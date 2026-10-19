# Pulled Changes Review — 12 October 2026

**Repositories reviewed:** `E-Comm_Web` and `E-Comm_Mobile`  
**Scope:** Changes present on the current local branches after the team pulls. Source/doc inspection only; no build, tests, or browser/device walkthrough.

## Web repository

### What arrived

- Seller workspace visual refresh: waybill modal, redesigned Shipments and Orders details, product filters, and new Vouchers, Complaints, and category-request screens. The detailed frontend record is [9 October progress](../logs/PROGRESS-2026-10-09.md).
- Buyer product/shop/checkout view refinements: stock-aware variant rows, product favorites, shop header/card updates, visible shipping line, and an optional seller note field.
- Admin visual updates: registration ID previews, User Management status filter, Seller Compliance detail dialogs, commission summary/rate UI, period-based reports, and Admin avatar component.
- New `/logistics-preview/*` routes and Company/Hub views use hard-coded sample data. They are preview routes with no Logistics authentication or database-backed operations, and must not be treated as real Logistics portal functionality.
- `nixpacks.toml` adds an optional Railway build/start recipe. Its start command runs `php artisan migrate --force` automatically and launches `php artisan serve`; this is separate from the documented Azure + Cloudflare Tunnel deployment and needs review before anyone uses it for a live database.
- PR #68 (`a5964e5`) adds the 9 October progress record only. The merged UI work is in the preceding commits, including `232217e`.

### Backend status to keep visible

- Existing Seller shipment routes/controllers support server-side search, status/courier/date filters, paginated rows, shipment details, cancellation, tracking updates, and the existing waybill route. The refresh changes their presentation.
- The Vouchers destination has no Seller voucher route/controller. The Complaints page has no Seller complaints listing route. Category-request UI has no request persistence endpoint. Sidebar links guarded by `Route::has` remain hidden where their named routes do not exist.
- Buyer checkout's optional seller note is only enabled when the controller passes its feature flag; no order note is stored. The product page's shipping amount falls back to ₱0 because checkout currently stores zero shipping fee and there is no quote service.
- Admin status notices in the refreshed screens matter: User Management's Active/Suspended/Deactivated filtering is client-side for the loaded page until the query supports those filters; Rejected remains server-filtered. The Admin commission rate editor changes the global rate used for historical report calculations.
- None of the Logistics preview screens replaces the authenticated Main Hub portal or adds scanner, SH custody, linehaul, or payout behavior.

## Mobile repository

- The Rider app has a redesigned animated splash/login flow, a refreshed four-step Rider registration screen, and a five-tab dashboard shell (Home, Parcels, Map, Earnings, Profile). The registration/auth client still calls the existing Rider API for OTP/Google/password registration and login.
- **Important route/data mismatch:** successful password and Google login now navigate to the new `DashboardScreen`. Its Home, Parcels, Map, Earnings, and delivery-detail content still contains prototype/sample data (including a fixed Bubukal map destination and hard-coded earnings). The API-backed `RiderWorkScreen` can fetch assigned parcels and submit supported scans, but the current login success path does not navigate to it. The UI refresh therefore does not make the new tabs live API workflows.
- Buyer registration has a new multi-step visual form with email verification modal, ID capture, address fields, terms checkbox, and success screen. The screen has no Buyer API client call; its OTP and successful submission are prototype UI and do not create a Buyer account.
- No new mobile API contract or dependency change was found in the latest Rider UI pull. The Buyer app remains a prototype outside the Rider integration.
- Details and existing gaps are documented in the [mobile Rider integration spec](../../E-Comm_Mobile/docs/features/rider-integration.md) and [mobile status](../../E-Comm_Mobile/docs/domain-feature-status.md); the mobile repo needs its own matching progress entry.

## Verification

Both repositories were clean at review start. This review reads Git history, routes/controllers, and current source. No automated tests, build, or app walkthrough were run; owner acceptance testing remains pending.

## Web backend follow-up (same day)

The Web gaps identified above were subsequently addressed in code for the fixed Admin 10% rate, Seller complaints listing, category request submission/Admin review, Seller vouchers with checkout redemption/cancellation release, Seller checkout notes, and server-side Admin account-status filtering. See [12 October progress](../logs/PROGRESS-2026-10-12.md) and the relevant feature specs. These changes require new migrations and owner verification. Shipping quotation remains open because the provisional ₱30–₱60 range is not a complete tariff rule.
