# Future Plan

## Web completion before mobile integration

1. **Close security and consistency gaps:** review middleware on every role route, fix the logistics courier redirect, scope every cross-role query, and define tests for authorization.
2. **Complete seller web operations:** product CRUD/review lifecycle, stock adjustments and history, seller reports, rating/reviews, notifications, and a visible rider-assignment state.
3. **Complete logistics fulfillment:** extend the current city/province routing, pickup/delivery rider assignments, manual hub handoffs, seller shipping label and three rider scan transitions with service-area/capacity rules, courier acceptance, linehaul scans/manifests, delivery completion, exceptions and returns. The [SOC5/SOC6 proposal](features/logistics/virtual-soc-checkpoints/spec.md) needs a trusted event source and lane rules before virtual checkpoints can appear as completed progress.
4. **Complete buyer protections:** cancellation/refund policy, product reviews, and any intended voucher/wishlist/support capabilities.
5. **Operational hardening:** audit events, queue/mail reliability, file privacy, error handling, backups, production configuration, and end-to-end tests.

## Mobile repository integration

The mobile repository is future work and is not part of the current Laravel implementation. A limited versioned rider API with expiring Sanctum tokens, assigned work and idempotent scans now exists in this web repository. When both repositories are ready:

- Review the [current rider API contract](features/courier/scan-api/spec.md) with the mobile team before connecting screens; extend `/api/v1` with documented delivery evidence and exception responses when those flows are agreed.
- Use a mobile-appropriate authentication mechanism, token expiry/revocation, device logout, and role/permission checks. Do not reuse Blade form routes or share the web session cookie as an undocumented API.
- Put order transitions, inventory rules, and actor authorization in reusable application services so web and mobile call the same rules.
- Start with read-only buyer catalog/order tracking, then courier assigned-work and proof-of-delivery only after shipment entities and policies are stable.
- Define upload limits/storage for proof photos, offline retries/idempotency keys, push-notification opt-in, API rate limits, and audit trails.
- Add contract tests and a staging environment that can run both repos against the same API; never let the mobile app connect directly to the production database.

## Documentation maintenance

Update the status matrix and affected specs with every completed web slice. Create API/mobile specs only when the contract and ownership decisions are agreed.
