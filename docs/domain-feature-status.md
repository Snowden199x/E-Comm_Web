# Domain and Feature Status

**Reviewed:** 25 September 2026. This is a code-based snapshot, not a promise of production readiness. See the individual feature specs for scope and known gaps.

| Domain | Current status | Implemented in code | Main gaps / next work |
|---|---|---|---|
| Admin | Partial to implemented by feature | Dashboard, registration review, user status actions, seller/product compliance, complaints, commission settings, reports, announcements/policies, notifications, messages, admin account management | Audit log coverage is limited to login sessions; access guards and role rules should be reviewed route-by-route; clarify financial/report definitions and lifecycle effects. |
| Buyer | Core web journey implemented; buyer registration uses verified email flow | Registration with session-backed OTP proof, login, product/category browse, cart, checkout grouped by seller, order list/detail/timeline, verified product reviews after receipt, buyer profile, messaging | Buyer cancel/refund, wishlist, vouchers, support tickets, and robust catalog/search filters are absent or incomplete. |
| Seller | Core seller operations implemented; logistics integration partial | Registration/approval, dashboard, product creation/edit/admin review, inventory filters/restock/history, Orders, shipments, Delivered Orders, product feedback/replies, reports, Vendo Support messages, account management, notifications and pinned header | Courier assignment/downstream delivery, carrier APIs/shipping quotation, bulk products, configurable thresholds, vacation mode remains incomplete. Buyer–seller messaging is present, with short-interval live refresh rather than broadcast delivery. New seller pages await manual verification. |
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
- Seller Delivered Orders, buyer product reviews, seller replies and admin review moderation are implemented. The feature specs document their current behavior.
- The [seller domain page](domains/Seller.md) links Reports, Messages, Account Management and Notifications. Buyer–seller order chat is available and includes product photos in shared order cards. Live updates currently use visibility-aware short polling.
