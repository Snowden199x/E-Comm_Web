# Rider Pickup Scan

**Status:** Implemented in the web API and Rider mobile client; owner verification pending
**Updated:** 28 September 2026

## Current behavior

Logistics manually assigns an approved Rider to a ready parcel. There is no Rider-side accept/decline step: the assignment appears in that Rider's authenticated work list. Seller remains responsible for preparing the parcel and making it ready for pickup.

At the seller stop, the assigned Rider scans the shipping-label tracking number and submits `scan_type: pickup` to `POST /api/v1/rider/scans`. The API verifies the Rider token, assigned Rider, approved Main Hub membership, expected `ready_for_pickup` state, and an idempotency UUID. In a transaction it changes the order to `picked_up` and writes scan/status events. The seller, buyer, and Logistics Center receive the resulting order update. A retry with the same scan key does not repeat the transition or notifications.

After pickup, the Rider takes the parcel to the origin Main Hub and scans `origin_arrival`; this changes `picked_up` to `at_sorting_center`. This is a center arrival scan, not proof that the buyer received the parcel. Main Hub sorting, Truck assignment, and physical SubHub custody remain separate steps. Rider and Truck operations are mobile-only; Logistics Center assignment and operations stay in the web portal.

## Remaining gaps

- No offline queue/reconciliation UI beyond retrying a persisted scan key in the mobile client.
- No independent pickup photo/signature/GPS evidence or exception workflow.
- Physical SubHub receipt/sorting, truck movement, manifests, and custody handoffs require the separate scanner app and backend contract.
- Owner testing on the target devices and deployed environment remains pending.

## Source evidence

`app/Http/Controllers/Api/RiderScanController.php`, `app/Services/OrderScanWorkflow.php`, `app/Http/Controllers/Logistics/DispatchController.php`, and the separate `E-Comm_Mobile/vendo_rider` client.

## Related documentation

See the [Rider scan API](../scan-api/spec.md), [Logistics domain](../../../domains/Logistics.md), and [order/logistics decisions](../../../order-logistics-flow-decisions.md).
