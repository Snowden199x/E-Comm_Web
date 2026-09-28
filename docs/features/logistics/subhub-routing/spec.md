# Virtual SubHub Route Checkpoints

**Status:** Partial: active route plans can assign an ordered virtual next checkpoint during Main Hub sorting; scanner progression and truck handoffs remain future work  
**Updated:** 28 September 2026

## Agreed route model

Use **SH** for *SubHub* (examples SH5, SH6, and SH3). An SH is a virtual route checkpoint named after a nearby municipality/locality. It has no exact street address and is not a registered Vendo Logistics Center or warehouse. For example, SH3 may be named **Pagsanjan** because that is the nearest known locality to a buyer in Luisiana, Laguna. The municipality is a representative route point, not a precise scan coordinate.

Each route plan connects an origin Main Hub to a destination Main Hub and contains an ordered list of virtual locality checkpoints. A parcel may use a plan only when that plan and every checkpoint are active. A Manila-to-Laguna plan could be Manila Main Hub → SH5 (Muntinlupa) → SH6 (Calamba) → Laguna Main Hub. A Calamba-to-Majayjay plan might include SH3 (Pagsanjan) before local delivery. These are examples only; operations must configure the paths Vendo intends to use. Routes can skip checkpoints when a separate active plan better matches the buyer's municipality.

At origin sorting, Vendo considers active plans for that exact Main Hub pair. It selects the plan whose nearest checkpoint municipality is closest to the buyer's municipality; an empty-stop plan uses the destination Main Hub as its comparison point. Approximate route length and configured priority resolve close ties. The seller-side location already determines the origin Main Hub, and SH locality checkpoints may also be configured near a seller area if the intended route needs one. If no valid plan exists, the order remains sorted with no selected route. Approximate straight-line coordinates do not establish roads, ferry availability, travel time, coverage, or transport feasibility.

The selected plan and next ordered checkpoint are recorded on the order. This is a route instruction only. It does not update parcel location or prove a truck handoff, checkpoint arrival, sorting, or custody transfer. `logistics_route_checkpoints` have no owner, address, or physical-site activation requirement. Configure checkpoint municipality names using the bundled PSGC reference codes; route plans and stops can be set up in DBeaver until an authorized route-management screen is built.

## Data setup

The additive migration creates:

- `logistics_route_checkpoints`: unique SH code, display name, representative province/municipality, optional PSGC codes, and inactive-by-default flag.
- `logistics_route_plans`: origin and destination Main Hub IDs, tie-break priority, and inactive-by-default flag.
- `logistics_route_plan_stops`: ordered checkpoint IDs for each plan (`position` starts at 1).
- `orders.route_plan_id`, `orders.route_step`, and `orders.next_route_checkpoint_id` for the selected plan and current planned stop.

It also creates inactive sample locality rows: SH5/Muntinlupa, SH6/Calamba, and SH3/Pagsanjan. These are locality suggestions from the agreed route examples, not verified facilities or preactivated routes. Existing rows from the earlier physical draft are copied as inactive locality labels.

In DBeaver, note the IDs of the origin/destination logistics centers, create a route-plan row for that exact pair, and add its stop rows using the checkpoint IDs in travel order. `name` is the nearby locality name (for example `Pagsanjan`); `code` is its route code (for example `SH3`). Keep checkpoints and plans inactive while editing; activate only after checking the intended route sequence. A direct route has no stop rows. Plan selection never considers records outside the exact origin/destination pair. The current web portal has no route-plan editor, so automatic selection starts only after these records are configured and activated.

The migration `2026_09_28_180000_create_logistics_sub_hubs_table.php` is an earlier physical-site draft that may already have run. The new route migration copies its checkpoint labels and municipality references into the virtual checkpoint table as inactive rows when present. Application logic no longer reads the legacy `logistics_sub_hubs` table or `orders.next_sub_hub_id`; those columns are retained as migration history/data and are not proof of a physical location.

## Examples and web/mobile boundary

For a Manila seller and Laguna buyer, the pickup rider takes the item to Manila Main Hub. Main Hub sorting selects a configured Manila → Laguna plan. If its stop order is SH5 then SH6, that sequence is shown as the planned path; the separate scanner app will later record scans when parcels reach the relevant route localities/handoffs. At the buyer's Main Hub, logistics can assign the delivery rider. Near Calamba, final mile may be assigned there; for a farther municipality such as Majayjay or Luisiana, a configured route may use SH3 named Pagsanjan before a local rider takes over. A virtual checkpoint must not itself trigger “Out for delivery.”

The Logistics web portal handles Main Hub sorting, route-plan selection, delivery assignment, monitoring, and reports. Rider, motorcycle courier, truck courier, and SH scanning interfaces are mobile-only and live in separate repositories. The web app currently has no SH scan API, truck manifest, linehaul assignment, checkpoint arrival event, custody event, checkpoint-to-checkpoint progression, or final-mile assignment from an SH. Implement those against the separate scanner app contract when that repository is available. Barcode/QR identifies the parcel but never authorizes a state change.

Legacy `soc5`, `soc6`, `to_soc5`, and `to_soc6` records remain historical/transitional values. They do not mean SH5/SH6 arrival and are shown in logistics UI as legacy route records.

See the [scan API](../../courier/scan-api/spec.md), [truck and linehaul dispatch](../company-truck-linehaul-dispatch/spec.md), [logistics status updates](../update-status/spec.md), and [order flow](../../../order-logistics-flow-decisions.md).
