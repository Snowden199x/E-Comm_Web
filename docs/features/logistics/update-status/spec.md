# Logistics Shipment Status Updates

**Status:** Facility-based scan and legacy status flows coexist; transport custody remains partial
**Reviewed:** 14 October 2026

> New sorts use real facility stops and the authenticated Logistics scanner. Virtual SH and SOC-coded stages remain for older orders only. See [province network and stations](../province-network-and-stations/spec.md).

Origin Logistics can scan arrival for a `picked_up` order at its Main Hub, then sort an `at_sorting_center` order and choose up to three real station stops. Cross-province routes append the destination Main Hub. A route plan alone leaves the parcel `sorted`; each arrival, sort, and intermediate departure requires a separate authenticated scan at the selected facility. The scan action verifies the expected stop and writes an idempotent facility scan and order status event. It reports operator action, not independent truck or custody proof.

For older virtual routes, the Rider API still accepts SOC-coded stages and the legacy destination Main Hub manual receipt gate. New facility routes reject SOC scans and manual destination receipt; the destination or final local station must scan arrival and sort before delivery Rider assignment. Seller, Buyer, and owning Logistics receive facility-specific notifications.

The mobile assigned-rider API still accepts pickup, origin-arrival, legacy inter-hub, out-for-delivery, and delivered scans with an idempotency key, row lock, scan event and order status event. The Logistics web scanner is the first-version facility scanner; the old separate SH app/API is paused. Truck manifests, cross-network custody acceptance, assigned Main Hub-to-local-station transport, delivery exceptions, and independent delivery evidence remain open. Rider and Truck interfaces are mobile-only.

See [virtual SH routing](../subhub-routing/spec.md), [mobile rider scan API](../../courier/scan-api/spec.md), and [truck/linehaul dispatch](../company-truck-linehaul-dispatch/spec.md).

Source: `app/Http/Controllers/Logistics/DispatchController.php`, `app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`.
