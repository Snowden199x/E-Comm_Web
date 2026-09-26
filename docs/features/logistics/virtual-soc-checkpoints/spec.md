# Virtual SOC5 and SOC6 Checkpoints

**Status:** Planned; documentation only
**Reviewed:** 26 September 2026

## Purpose and ownership

SOC5 and SOC6 are logical checkpoints for tracking travel between two real, approved main logistics hubs. They are not physical sorting facilities in this proposal. They do not require logistics accounts or address records and cannot receive, sort, or assign riders. The origin and destination hubs retain the real operational responsibilities. The owner's intended flow advances each leg when an authorized courier scans the parcel label; the current code does not implement the SOC scans.

## Example route

| Scan or confirmation | Tracking update after the action | Next step |
|---|---|---|
| 1. Assigned courier scans when collecting from the Laoag seller. | Picked up from seller. | Bring parcel to the seller-area Laoag main hub. |
| 2. Authorized courier scans at the Laoag main hub; the hub receives/sorts it. | At origin hub; next route leg is SOC5. | Travel through the inter-hub route. |
| 3. Authorized courier scans for SOC5. | SOC5 logical checkpoint passed; next route leg is SOC6. | Continue to SOC6. |
| 4. Authorized courier scans for SOC6. | SOC6 logical checkpoint passed; next destination is Calamba main hub. | Continue to the buyer-area hub. |
| 5. Calamba main hub confirms physical receipt. | Arrived at destination hub. | Its logistics team assigns the delivery rider. |
| 6. Delivery rider completes delivery; buyer later confirms receipt. | Delivered, then completed under the eventual delivery workflow. | Close the order. |

The route is an example for a Laoag, Ilocos Norte seller and a Calamba, Laguna buyer. The first four steps are the courier scans described by the owner. The fifth is a separate destination receipt confirmation: the SOC6 scan cannot claim that Calamba already has the parcel. Actual origin/destination centers still come from approved center coverage and the order's structured addresses. Do not treat the illustrated route as a distance calculation or a claim that every national shipment uses this exact lane. A same-hub order does not need SOC5/SOC6.

## Timeline and integrity rules for later implementation

- The buyer and seller may see SOC5 and SOC6 as **upcoming route steps** once the lane is assigned. An upcoming step is not a completed scan or physical location.
- Show a checkpoint as **passed** only after an assigned, approved courier scans the parcel label for that leg and an attributable event records its sequence and server time. The event wording must say it is a route checkpoint, not arrival at an SOC warehouse.
- A courier scans the parcel's tracking barcode/QR; SOC5/SOC6 are selected scan stages, not separate physical codes printed on a facility. A virtual SOC scan must not transfer custody to an imaginary facility. Physical pickup and destination receipt retain their own confirmation events.
- Keep progress ordered and idempotent so a retry cannot duplicate milestones or advance SOC6 before SOC5. Seller/buyer notifications should describe only confirmed events.
- An exception, return, or skipped leg must be visible as its real event; it must not be hidden by automatically completing the planned SOC steps.

## Decisions required before coding

1. Define which assigned courier is authorized to scan each inter-hub leg and how handoffs between different couriers are recorded.
2. Define the operational trigger for each virtual SOC scan, given that there is no physical SOC site; scanning cannot assert geographic arrival there.
3. Define which origin/destination lanes use both codes, one code, or neither.
4. Define the visible labels and exception handling for delays, rerouting, returns, and failed handoffs, plus whether a linehaul/manifest record is required.

Existing behavior is documented in [order and logistics flow decisions](../../../order-logistics-flow-decisions.md) and [logistics shipment status updates](../update-status/spec.md).
