# Future Plan

## Web completion and remaining mobile integration

1. **Close security and consistency gaps:** review middleware on every role route, retire the unsupported courier web-login redirect, scope every cross-role query, and define tests for authorization.
2. **Complete seller web operations:** product CRUD/review lifecycle, stock adjustments and history, seller reports, rating/reviews, notifications, and a visible rider-assignment state.
3. **Complete logistics fulfillment:** replace legacy SOC5/SOC6 scan states with SH locality scans (examples SH5, SH6, SH3). The web app selects among active ordered route plans using the nearest configured checkpoint municipality, and origin Logistics can assign a Truck Rider to a sorted cross-hub parcel; route setup remains manual in DBeaver. Implement authorized SH arrival events, route-step progression, truck movement/manifests and custody handoffs, local Rider dispatch from a checkpoint, and alternate/long-haul legs. Rider, truck, and scanner interfaces are mobile-only; the web repository owns protected backend APIs and Logistics Center web operations. Add independent delivery proof, exceptions, and returns after ownership and policies are defined. Rider work is assigned by Logistics without a Rider accept/decline step. The Rider API's new linehaul assignments are read-only; it does not yet implement SH locality scans. See [virtual SH routing](features/logistics/subhub-routing/spec.md) and [truck/linehaul dispatch](features/logistics/company-truck-linehaul-dispatch/spec.md).
4. **Complete buyer protections:** cancellation/refund policy, product reviews, and any intended voucher/wishlist/support capabilities.
5. **Operational hardening:** audit events, queue/mail reliability, file privacy, error handling, backups, production configuration, and end-to-end tests.

## Mobile repository integration

The separate E-Comm_Mobile repository has an existing rider client for registration, approved login, assigned work, and the seven legacy/current scan transitions supported here. The owner plans a separate repository for SH locality scanners; its mobile docs will be handled when that repository is provided. The buyer client and fuller rider lifecycle remain future work. For the next mobile/backend slices:

- Extend the [current rider API contract](features/courier/scan-api/spec.md) with documented delivery evidence and exception responses when those flows are agreed.
- Use a mobile-appropriate authentication mechanism, token expiry/revocation, device logout, and role/permission checks. Do not reuse Blade form routes or share the web session cookie as an undocumented API.
- Put order transitions, inventory rules, and actor authorization in reusable application services so web and mobile call the same rules.
- Start buyer integration with read-only catalog/order tracking. Add courier proof of delivery only after shipment entities and policies are stable.
- Define upload limits/storage for proof photos, offline retries/idempotency keys, push-notification opt-in, API rate limits, and audit trails.
- Add contract tests and a staging environment that can run both repos against the same API; never let the mobile app connect directly to the production database.

## Documentation maintenance

Update the status matrix and affected specs with every completed web slice. Create API/mobile specs only when the contract and ownership decisions are agreed.
