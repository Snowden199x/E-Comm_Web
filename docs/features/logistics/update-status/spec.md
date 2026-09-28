# Logistics Shipment Status Updates

**Status:** Partial
**Reviewed:** 26 September 2026

The origin center can manually confirm arrival for a `picked_up` order and sorting for an `at_sorting_center` order. For different origin and destination centers, the assigned pickup rider scans three virtual inter-hub milestones: `sorted → to_soc5 → to_soc6 → in_transit_to_hub`. The destination center can then manually confirm actual receipt (`at_destination_hub`). Each status action checks actor scope/current status under a row lock and records an order status event. The older direct manual send action has been removed; legacy parcels already `in_transit_to_hub` can still be received.

The manual actions do not prove a scan, truck movement, or custody transfer. The assigned-rider API accepts pickup, origin-arrival, three virtual route milestones, out-for-delivery, and delivered scans with an idempotency key, row lock, scan event and order status event. It records the approved center and server time, then creates logistics, seller, and buyer notifications; it does not claim GPS or independent proof of delivery. Manifest/linehaul, courier acceptance, delivery exceptions, and independent delivery evidence remain open. See [rider scan API](../../courier/scan-api/spec.md).

SOC5 and SOC6 are [virtual route checkpoints](../virtual-soc-checkpoints/spec.md) between real hubs. Their scan events are recorded in sequence; the displayed `to_soc5` and `to_soc6` statuses mean the next leg, not physical arrival at a facility. Destination-hub receipt remains a separate confirmation after the final scan.

Source: `app/Http/Controllers/Logistics/DispatchController.php`, `app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`.
