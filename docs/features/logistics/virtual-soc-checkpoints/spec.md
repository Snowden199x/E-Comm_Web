# Virtual SOC5 and SOC6 Checkpoints

**Status:** Implemented for the assigned origin pickup rider; owner verification pending
**Updated:** 27 September 2026

SOC5 and SOC6 are virtual route checkpoints between two different approved main logistics hubs. They have no logistics account, warehouse address, independent rider roster, or custody record. The origin pickup rider remains the assigned scanner for all three inter-hub events in this first version. This is a temporary operational limit until linehaul rider handoff and manifests are defined.

For a cross-hub parcel, the seller marks it ready, the origin hub assigns a pickup rider, and the seller can print the label. That rider scans `pickup` to collect it, then `origin_arrival` at the origin hub. The origin hub marks sorting complete. The same rider scans the label three more times in sequence:

| Rider scan | Stored status | Confirmed milestone | Next destination |
| --- | --- | --- | --- |
| `soc5` | `to_soc5` | Scan after sorting at origin hub | Virtual SOC5 checkpoint |
| `soc6` | `to_soc6` | Virtual SOC5 route checkpoint scanned | Virtual SOC6 checkpoint |
| `destination_hub` | `in_transit_to_hub` | Virtual SOC6 route checkpoint scanned | Actual destination main hub |

Only after this third scan can the destination hub confirm physical receipt. Its approved delivery rider is then assigned and scans `out_for_delivery`. A same-hub order bypasses all virtual checkpoint statuses and can receive its delivery rider directly after sorting. Parcels that reached `in_transit_to_hub` under the older direct handoff remain receivable; their history must not invent SOC scans.

The API locks the order, verifies the rider and hub, requires a resolved different destination hub, enforces the previous status, and records each scan with a unique per-order retry UUID, server timestamp, and status event. Seller and buyer notifications describe the confirmed scan and next leg, not physical presence at SOC5 or SOC6. The printed barcode and QR both contain the stable tracking number only. Delivery completion, scan handoff to another rider, exceptions, proof of travel, and manifest tracking remain unimplemented.

See the [scan API](../../courier/scan-api/spec.md), [logistics status updates](../update-status/spec.md), and [order flow](../../../order-logistics-flow-decisions.md).
