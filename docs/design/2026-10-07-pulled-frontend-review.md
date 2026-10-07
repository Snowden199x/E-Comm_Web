# Frontend pull review — 7 October 2026

**Scope:** Review of UI commits merged from the frontend branch in PRs #58, #59, #60, and #61. This document records what the pulled UI currently does, what is already backed by web code, and the backend work to consider before the next implementation pass. No application code was changed for this review.

**Follow-up:** The backend items accepted after this review were implemented in the same work session; see [7 October progress](../logs/PROGRESS-2026-10-07.md) and the updated feature specs for current behavior. The sections below remain the original pre-implementation review.

## Pulls reviewed

- **PR #58, `dd5c58a` (6 October):** Buyer product, shop, cart, checkout, order, and notification views; Seller dashboard, product and order screens; Admin Seller Compliance and protected document preview. The feature specs and dated role progress entries already describe most of these changes.
- **PR #59, `0d7ff8a` (7 October):** Admin shell, dashboard, registrations/User Management stat cards, Seller Compliance popup, Complaints and Disputes views, and a Buyer shop-search view. The Admin progress entry documents the visual changes and identifies the read/query and filter gaps.
- **PR #60, `066c9b1` (7 October):** Buyer dashboard, cards, account/settings, messages, notifications, orders, and shared layout refresh; browser-local saved items and device-local preferences.
- **PR #61, `f724efd` (7 October):** Buyer progress documentation only.

All reviewed application changes are presentation-layer work: Blade, CSS, JavaScript, and Vite inputs. These pulls do not add Buyer wishlist persistence, account-level settings storage, or the missing shop-search route/controller. Existing server behavior remains authoritative for submitted forms and protected operations.

## Buyer: saved items and device settings

- Saved-item hearts, header count, slide-over, Dashboard row, and Settings section use browser `localStorage`, keyed per signed-in user. They do not use a database wishlist, sync between browsers/devices, or alter the cart. Product data is a browser-side snapshot and may become stale if a listing changes.
- Appearance, reduced-motion, and notification-sound preferences are also stored in that browser, not on the account. Notification-type choices and email notification preferences are not server-backed settings.
- The account screen combines Profile, Password and security, Settings, Saved items, Notifications, and Privacy/policies under one route with query-string deep links. Existing profile and password form names/routes remain in use.
- Dashboard and product cards now expose saved-item actions and optional discount/rating/sold/shop details. Those details only appear when their fields are supplied by the server; the pull does not add those product metrics.

**Implementation direction:** If saved items must follow the Buyer across devices, add a Buyer-owned wishlist table and scoped list/toggle/remove operations, validate that saved products remain visible, and render the saved list from server data. If preferences should follow the account, add user-scoped settings storage and validation. Keep local storage as a temporary UI fallback only if desired.

Other Buyer follow-ups documented with this refresh:

- A separate `/buyer/settings` route is optional; the current account page already switches sections using query-string tabs.
- Avoid one lazy status-event lookup per cancelled order by loading cancellation actor/reason efficiently. The UI reads the status event note today.
- Supply rating average, delivered/completed sold count, and `compare_at_price` only where the backend has valid source data. The card treats these values as optional.
- Dashboard announcements are already supported; announcement slide artwork is optional and needs a data field only if the product wants image banners.
- Seller names in message lists, support-message timestamps, and unread Support count can be added to the response if the refreshed UI should display them. These are query/response improvements, not new messaging workflows.
- The Buyer pages hide cancellation after preparation, but the earlier backend review found the server cancellation service still accepts `preparing` and `ready_for_pickup`. Confirm and align that server rule before relying on the UI restriction. The order detail also retains a legacy `returned` terminal-state check.

## Buyer: shop discovery

`buyer/sellers/index.blade.php` provides a shop-search page, but `buyer.sellers.index` and its controller query are absent. Product-list/header/footer links are conditional on that route, so this screen is not reachable through the application yet. In-shop search and sorting are likewise only displayed when filter data is provided.

**Implementation direction:** Add a Buyer-authenticated route/controller that searches approved, active, non-archived sellers by shop name or account name, paginates results, and returns approved shop cards. Do not search email or phone. Add in-shop search/sort only with server-side filtering and the same product visibility rules as the shop page. Load product counts and card metrics in the query rather than calculating per card.

## Admin: pulled workflow UI

- The Admin dashboard attention card reads counts from the view when available. Those counts should be supplied by `DashboardController`; confirm the sales and returned/refunded definitions before extending reporting.
- Complaints status/kind controls are client-side for the current page because `ComplaintController::filteredComplaints()` does not apply those parameters. Counts and visible rows can therefore diverge across pages.
- Seller Compliance row popups load seller products and warning/violation data from the Blade partial, creating several queries per row. Move this to a scoped/eager-loaded controller response if page size grows.
- The Complaints Messages panel is an empty state and party actions are `mailto:` links. There is no case-linked conversation workflow; do not present this as implemented case messaging.
- Registrations and User Management card/filter and document-preview redesigns keep existing route/form contracts. Rider approval remains owned by the linked Logistics Center.

The detailed descriptions of each Admin screen are in `docs/logs/PROGRESS-2026-10-07.admin-entry.md` and the Admin feature specs.

## Existing work documented in PR #58

PR #58 already updated Buyer and Seller feature specs and role progress entries. Its UI additions include Buyer checkout/order/product/shop/notification refreshes, Seller dashboard/order/product refreshes, and Admin Seller Compliance/document preview. Treat those screens as UI until the relevant spec confirms the server behavior. In particular, do not infer shop search, buyer wishlist persistence, extra saved addresses, or notification enhancements from a rendered control alone.

## Suggested implementation order

1. Decide whether saved items and preferences need account-level persistence; if yes, implement the schema, scoped endpoints, and server-rendered data before treating the UI as durable.
2. Implement Buyer shop search and in-shop filters server-side, with approved/active seller and product visibility constraints.
3. Align Buyer cancellation authorization on the server with the intended pre-preparation rule; keep the UI and endpoint consistent.
4. Implement Admin complaint filters in the query and move dashboard attention counts out of Blade.
5. Define case-linked messaging participants, storage, and notification rules before connecting the Admin Messages panel.
6. Revisit Seller Compliance query placement if current row volume makes the view-side queries expensive.

## Verification boundary

This was a source and Git diff review only. No feature code was implemented, and no tests, build, or browser walkthrough were run. The owner should verify the pulled UI in the running app before implementation decisions are considered accepted.

## Source references

- Pull review: commits `dd5c58a`, `0d7ff8a`, `066c9b1`, `f724efd`.
- Buyer progress: `docs/logs/PROGRESS-2026-10-07.buyer-entry.md`.
- Admin progress: `docs/logs/PROGRESS-2026-10-07.admin-entry.md`.
- Routes: `routes/web.php`; Buyer views: `resources/views/buyer/`; Admin views: `resources/views/admin/`.
