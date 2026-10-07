# Logistics Shipment Status Updates

**Status:** Partial
**Reviewed:** 28 September 2026

Origin logistics can manually confirm arrival for a `picked_up` order and sorting for an `at_sorting_center` order. During sorting, the web app selects an active configured Main Hub route plan for cross-hub orders and saves its first virtual SH locality as the planned next checkpoint. A checkpoint is a nearby municipality label such as Pagsanjan/SH3, not an exact street address or registered logistics site. This route instruction alone leaves the order `sorted` and does not confirm a physical scan, truck movement, or custody transfer.

For different origin and destination centers, the current rider API still accepts legacy SOC-coded inter-hub stages: `sorted → to_soc5 → to_soc6 → in_transit_to_hub`. Those values do not mean the parcel reached an SH locality. Destination logistics may manually confirm Main Hub receipt (`at_destination_hub`). Each implemented action checks actor scope/current status under a row lock and records an order status event. Legacy parcels already `in_transit_to_hub` can still be received.

The mobile assigned-rider API accepts pickup, origin-arrival, legacy inter-hub, out-for-delivery, and delivered scans with an idempotency key, row lock, scan event and order status event. It records the approved center and server time, then creates logistics, seller, and buyer notifications; it does not claim GPS or independent proof of delivery. A future, separate SH scanner app must record actual checkpoint arrivals, authorize its scanner identity, validate the order's expected route step, and notify seller/buyer. Truck/manifest, custody handoffs, local rider pickup from a checkpoint, delivery exceptions, and independent delivery evidence remain open. Courier, rider, and truck interfaces are mobile-only.

See [virtual SH routing](../subhub-routing/spec.md), [mobile rider scan API](../../courier/scan-api/spec.md), and [truck/linehaul dispatch](../company-truck-linehaul-dispatch/spec.md).

Source: `app/Http/Controllers/Logistics/DispatchController.php`, `app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`.
