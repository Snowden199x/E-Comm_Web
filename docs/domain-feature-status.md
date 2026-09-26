# Domain and Feature Status

**Reviewed:** 26 September 2026. This is a code-based snapshot, not a promise of production readiness. See the individual feature specs for scope and known gaps.

| Domain | Current status | Implemented in code | Main gaps / next work |
|---|---|---|---|
| Admin | Partial to implemented by feature | Dashboard, registration review, user status actions, seller/product compliance, complaints, commission settings, reports, announcements/policies, operational notifications (excluding buyer purchases), support messages and admin-only support-thread deletion, admin account management | Assignment history is recorded, but broader audit log coverage is limited; access guards and role rules should be reviewed route-by-route; clarify financial/report definitions and lifecycle effects. |
| Buyer | Core web journey implemented; buyer registration uses verified email flow | Registration with session-backed OTP proof, login, product/category browse, cart grouped by store with selected-item checkout, seller-specific orders, order list/detail/timeline, verified product reviews after receipt, buyer profile, messaging | Buyer cancel/refund, wishlist, vouchers, support tickets, and robust catalog/search filters are absent or incomplete. |
| Seller | Core seller operations implemented; logistics integration partial | Registration/approval, dashboard, product creation/edit/admin review, inventory filters/restock/history, Orders, shipments and origin-logistics-branded 4 × 6 barcode/QR label, Delivered Orders, feedback/replies, reports, Vendo Support, account management, notifications; seller readiness and automatic center routing | Courier acceptance and downstream delivery, carrier APIs/shipping quotation, bulk products, configurable thresholds, vacation mode remain incomplete. Buyer–seller messaging uses short-interval live refresh. New label and scan flow await owner verification. |
| Logistics | Partial | Center registration/login and protected sidebar/account pages; center-scoped Rider Management for applications and approval; stage-filtered Incoming Parcel Management, Parcel Sorting and Delivery assignments; seller/buyer location routing; origin pickup assignment and arrival/sorting; manual hub send/receipt; destination delivery assignment; rider scan notifications | Delivery Monitoring, Reports and Messages sidebar pages are placeholders. SOC5/SOC6 virtual inter-hub checkpoints are documented but not implemented; current transit is one direct hub handoff. No physical-distance/capacity rules, courier acceptance, truck/manifest linehaul, delivery completion, or exception flow. Unresolved routing requires operational review. Existing verification-document URLs use public storage. |
| Courier | Backend scan contract; separate client planned | Courier role/profile; center approval and assignment; versioned token login, assigned-work list, idempotent assigned pickup/origin-arrival/out-for-delivery scan endpoint and logout | No courier browser dashboard, mobile app, acceptance, delivery proof/completion, incident flow, or history. Existing courier browser-login redirect points to a missing dashboard route. |
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
