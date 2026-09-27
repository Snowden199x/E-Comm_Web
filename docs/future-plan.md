# Future Plan

## Web completion and remaining mobile integration

1. **Close security and consistency gaps:** review middleware on every role route, fix the logistics courier redirect, scope every cross-role query, and define tests for authorization.
2. **Complete seller web operations:** product CRUD/review lifecycle, stock adjustments and history, seller reports, rating/reviews, notifications, and a visible rider-assignment state.
3. **Complete logistics fulfillment:** extend the current city/province routing, pickup/delivery rider assignments, virtual SOC scans, destination hub receipt, seller shipping label and seven rider scan transitions with service-area/capacity rules, courier acceptance, reassignment between linehaul riders, manifests, independent delivery proof, exceptions and returns. The [SOC5/SOC6 implementation](features/logistics/virtual-soc-checkpoints/spec.md) currently uses the origin pickup rider for the entire inter-hub sequence.
4. **Complete buyer protections:** cancellation/refund policy, product reviews, and any intended voucher/wishlist/support capabilities.
5. **Operational hardening:** audit events, queue/mail reliability, file privacy, error handling, backups, production configuration, and end-to-end tests.

## Mobile repository integration

The separate E-Comm_Mobile repository now has a rider client for registration, approved login, assigned work, and the seven scan transitions currently supported here. The buyer client and fuller rider lifecycle remain future work. For the next mobile/backend slices:

- Extend the [current rider API contract](features/courier/scan-api/spec.md) with documented delivery evidence and exception responses when those flows are agreed.
- Use a mobile-appropriate authentication mechanism, token expiry/revocation, device logout, and role/permission checks. Do not reuse Blade form routes or share the web session cookie as an undocumented API.
- Put order transitions, inventory rules, and actor authorization in reusable application services so web and mobile call the same rules.
- Start buyer integration with read-only catalog/order tracking. Add courier proof of delivery only after shipment entities and policies are stable.
- Define upload limits/storage for proof photos, offline retries/idempotency keys, push-notification opt-in, API rate limits, and audit trails.
- Add contract tests and a staging environment that can run both repos against the same API; never let the mobile app connect directly to the production database.

## Documentation maintenance

Update the status matrix and affected specs with every completed web slice. Create API/mobile specs only when the contract and ownership decisions are agreed.
