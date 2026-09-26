# Logistics Shipment Status Updates

**Status:** Partial
**Reviewed:** 26 September 2026

The origin center can manually confirm arrival for a `picked_up` order and sorting for an `at_sorting_center` order. For different origin and destination centers, origin can send a `sorted` parcel to the selected destination (`in_transit_to_hub`); the destination center can manually confirm receipt (`at_destination_hub`). Each status action checks center scope/current status under a row lock and records an order status event. The send action also records the two centers in `order_logistics_assignments`.

These manual actions do not prove a scan, truck movement, or custody transfer. A separate assigned-rider API now accepts pickup, origin-arrival, and out-for-delivery scans with an idempotency key, row lock, scan event and order status event. The API records the approved center and server time, then creates logistics, seller, and buyer notifications; it does not claim GPS or proof of delivery. Manifest/linehaul, courier acceptance, delivery completion and exception actions remain open. See [rider scan API](../../courier/scan-api/spec.md).

SOC5 and SOC6 are [proposed virtual route checkpoints](../virtual-soc-checkpoints/spec.md) between real hubs. The owner's intended flow advances them through authorized courier scans of the parcel label after origin-hub arrival, in sequence. They are not implemented by the current single `in_transit_to_hub` status and must not be presented as completed physical arrivals. Destination-hub receipt remains a separate confirmation after the SOC6 scan.

Source: `app/Http/Controllers/Logistics/DispatchController.php`, `app/Models/Ecommerce/Order.php`, `app/Models/Ecommerce/OrderStatusEvent.php`.
