# Domain and Feature Status

**Reviewed:** 24 September 2026. This is a code-based snapshot, not a promise of production readiness. See the individual feature specs for scope and known gaps.

| Domain | Current status | Implemented in code | Main gaps / next work |
|---|---|---|---|
| Admin | Partial to implemented by feature | Dashboard, registration review, user status actions, seller/product compliance, complaints, commission settings, reports, announcements/policies, notifications, messages, admin account management | Audit log coverage is limited to login sessions; access guards and role rules should be reviewed route-by-route; clarify financial/report definitions and lifecycle effects. |
| Buyer | Core web journey implemented; buyer registration uses verified email flow | Registration with session-backed OTP proof, login, product/category browse, cart, checkout grouped by seller, order list/detail/timeline, buyer profile, messaging | Buyer cancel/refund, reviews, wishlist, vouchers, support tickets, and robust catalog/search filters are absent or incomplete. Verified purchase reviews are now specified in docs, not implemented. |
| Seller | Core seller operations implemented; logistics integration partial | Registration/approval, dashboard, product creation/edit/admin review, inventory filters/restock/history, Orders, shipment filters/tracking/details, pickup handoff and cancellation before pickup | Completed Orders and Feedback pages are documented but not implemented. Courier assignment/downstream delivery, carrier APIs/shipping quotation, bulk products, configurable thresholds, reports, reviews and vacation mode remain incomplete. |
| Logistics | Partial | Center registration/login and dashboard; center-scoped rider application review, approve/reject | Some routes lack explicit auth middleware; rider assignment, pickup scans, sorting, hubs, linehaul, and delivery workflow are missing. Login refers to a courier dashboard route not present in `routes/web.php`. |
| Courier | Data model only / planned | Courier role and profile fields; logistics center can review a courier profile | No courier dashboard/routes, delivery assignment, proof of delivery, incident flow, history, or mobile client. |
| Shared | Partial | Orders/items/status events, notifications, announcements, policies, messaging, commission settings, complaints | No unified shipping quotation, settlement/payout ledger, complete shipment entity, or API contract for mobile. |

## Evidence by code area

- Routes: [`routes/web.php`](../routes/web.php), [`routes/auth.php`](../routes/auth.php).
- Models and profiles: [`app/Models`](../app/Models).
- Web UI: [`resources/views`](../resources/views).
- Database shape: [`database/migrations`](../database/migrations).

## Interpretation notes

- A Blade screen alone does not make an operation functional. Several sidebar destinations still point to `#`.
- A status value or profile column does not mean the role can update it; Courier and several logistics stages currently lack action routes.
- Sample orders created by `SellerDemoSeeder` are development data and are not operational fulfillment records.
- The seller [Completed Orders](features/seller/completed-orders/spec.md) and [Feedback](features/seller/review-management/spec.md) backend contracts are implementation plans; routes and review records for these screens have not been added.
