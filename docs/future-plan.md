# Future Plan

## Web completion and remaining mobile integration

1. **Close security and consistency gaps:** review middleware on every role route, retire the unsupported courier web-login redirect, scope every cross-role query, and define tests for authorization.
2. **Complete seller web operations:** product CRUD/review lifecycle, stock adjustments and history, seller reports, rating/reviews, notifications, and a visible rider-assignment state.
3. **Verify and finish Logistics Company / Province Hub / Station Hub operations:** the web hierarchy now includes primary overlap coverage, Driver onboarding/review, Station pickup/delivery tasks, trip manifests and departure/arrival, hierarchy scanning, cross-company vehicle manifests with ETA, facility exceptions and same-company detours, private recipient/photo delivery proof, scoped reports/messages/account pages and short-poll notifications. Rider task issue reports and Company Transport Driver mobile login/trip actions are now connected to the matching Web APIs. Run the pending additive migrations, then owner-verify the full Seller → Rider → facilities → Buyer flow. Still needed: finalized settlement policy, SLA/revenue reports, PDF/Excel exports, and owner verification. Preserve legacy SOC/SH scan history. See [province network and stations](features/logistics/province-network-and-stations/spec.md).
4. **Complete Buyer voucher protections:** add stacking only after a rule is defined; define return/refund policy, strengthen account-backed saved-item unavailable states, add support tickets, and have the owner verify cancellation/voucher/review journeys. Checkout voucher selection and first-activation alerts are implemented in code and await migration and owner verification.
5. **Operational hardening:** audit events, queue/mail reliability, file privacy, error handling, backups, production configuration, and end-to-end tests.

## Mobile repository integration

The separate E-Comm_Mobile repository now connects Rider task issue reports and Company Transport Driver trip execution to the Web API, in addition to approved Rider login, assignment scans, Truck facility scans, and private delivery proof. The separate E-Comm_MSA scanner work was based on the legacy virtual-SH model and is paused. The buyer client and fuller Rider lifecycle remain future work. For the next mobile/backend slices:

- Define the shipping-fee share and immutable settlement ledger before enabling Rider Earnings. Do not display estimates as payouts.
- Use a mobile-appropriate authentication mechanism, token expiry/revocation, device logout, and role/permission checks. Do not reuse Blade form routes or share the web session cookie as an undocumented API.
- Put order transitions, inventory rules, and actor authorization in reusable application services so web and mobile call the same rules.
- Start buyer integration with read-only catalog/order tracking. Add courier proof of delivery only after shipment entities and policies are stable.
- Define upload limits/storage for proof photos, offline retries/idempotency keys, push-notification opt-in, API rate limits, and audit trails.
- Add contract tests and a staging environment that can run both repos against the same API; never let the mobile app connect directly to the production database.

## Documentation maintenance

Update the status matrix and affected specs with every completed web slice. Create API/mobile specs only when the contract and ownership decisions are agreed.
