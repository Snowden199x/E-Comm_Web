# Order and Logistics Flow Decisions

## Province network decision (14 October 2026)

The implemented first version uses one approved Logistics network account per province, one Main Hub and optional actual stations under that account. A Laguna account represents the whole Laguna network. A station receives, sorts, holds, or hands off parcels; a town that a truck merely passes through is not a station. New routes use actual Main Hubs and stations. The authenticated Logistics portal opens a browser-camera scanner modal at the selected facility.

The province address catalog remains complete for ordinary addresses. A Logistics account's coverage is derived from its Main Hub address province; the registration form does not ask for a second coverage value. Claimed provinces are disabled in the Logistics registration address selector, while server validation and database uniqueness block simultaneous duplicate claims. Admin rejection releases a pending claim. Admin replacement and duplicate legacy-account resolution remain open in the [province network and stations spec](features/logistics/province-network-and-stations/spec.md).

The live `/logistics/facilities` pages own real network facilities. `/logistics-preview/*` still shows hard-coded sample screens. Legacy virtual SH route plans and scanner history remain preserved and separate from the new facility scan records.

## Current code behavior

## Current status sequence and owners

Buyer checkout creates `placed` seller-specific orders. Seller can accept, prepare, and move an order to `ready_for_pickup`; seller cannot mark physical pickup. Readiness invokes `OrderRoutingService`, which resolves the Seller and Buyer provinces to their unique approved active Logistics accounts. Unclaimed or inactive coverage leaves routing unresolved. There is no Admin dispatch action.

The origin Main Hub verifies pickup and assigns an approved Motorcycle/Van/L300 Rider linked to it. The Rider scan moves `ready_for_pickup → picked_up`. Arrival at origin Main Hub can be scanned in the browser modal (`picked_up → at_sorting_center`); the earlier manual arrival route remains. On sorting, Logistics selects up to three actual station stops in travel order. A cross-province route places origin stations before the destination Main Hub and destination stations after it; a local route can end at a station or go directly to local delivery. The route is saved as `order_facility_stops`.

The planned path does not mark a parcel as arrived. The origin Main Hub assigns an approved Truck Rider for cross-province linehaul. The Logistics browser scanner and assigned Truck Rider app can scan the origin Main Hub and actual ordered stops. The Rider API exposes the next valid facility as `planned_route.scan_target`; its facility-scan endpoint checks the assigned Truck Rider, facility, and order route. A scan at a receiving facility records arrival and sorting together. Intermediate facility departure is a separate scan before the next stop can receive the parcel. The final stop must be scanned before a local Rider can be assigned. Each scan creates facility-specific Seller/Buyer/Logistics notifications and remains an operator report rather than independent proof of truck movement or custody. Legacy `to_soc5`/`to_soc6` actions are rejected for new facility routes; historical virtual routes retain their old gate.

The assigned destination rider scans `assigned_to_rider → out_for_delivery`, then scans after handover for `out_for_delivery → delivered`. The latter is the rider's report of delivery, not independent buyer receipt proof. Buyer receipt confirmation moves `delivered → completed`, after which product rating is available; the buyer's final progress label is **Rate Product**. `delivery_failed` and `returned` remain in `Order::STATUSES`, but exception routes are not built. Buyer may cancel only while `placed` or `confirmed`, before Seller preparation. Seller may cancel through `ready_for_pickup`, before Rider pickup. Both require a reason; cancellation restores reserved product/variant stock and records the actor and reason in the order status event. Seller can also decline a newly placed order with a reason. Implemented scan transitions create scan and status events with center/city, and notify seller, buyer, and center owner. Manual hub transitions also notify seller and buyer with center/city context. Mobile scanner applications remain in separate repositories.

## Location data and limits

Checkout requires a selected province and city/municipality from the bundled PSGC snapshot and saves both codes and names on the order. The free-text address holds street/barangay detail; the server appends city and province. Older orders have only free-text addresses and cannot safely infer a destination hub. Use `php artisan orders:route-ready` after migrations to assign eligible existing ready orders; approving or reactivating a logistics center also retries unresolved ready orders.

The PSGC snapshot is a fixed reference, not proof of current administrative boundaries. Province matching uses the unique claim; it does not estimate road/ferry links, capacity, service coverage, or travel time. Reassignment, exceptions, consolidation, truck manifests, delivery proof, and refunds remain future work. The Rider scan contract is documented in the [rider scan API](features/courier/scan-api/spec.md).

## Legacy virtual SH locality checkpoints (superseded for future design)

The following records explain the existing implementation and data only. They are not the target flow. New route planning must use [province network facilities](features/logistics/province-network-and-stations/spec.md), not virtual SH checkpoints.

Use **SH** for *SubHub*, with examples SH5, SH6, and SH3. An SH has a code and a named municipality/locality that represents the nearest known place for a buyer or seller area; it has no exact street address and is not a registered Vendo logistics center. For example, SH3 may be named **Pagsanjan** because Pagsanjan is the nearest named locality to a buyer in Luisiana, Laguna. The locality point helps describe and choose a configured route. It does not imply a particular warehouse or exact scan coordinate.

Each route plan connects an origin Main Hub to a destination Main Hub and stores an ordered sequence of SH localities. For example, Manila Main Hub → SH5 (Muntinlupa) → SH6 (Calamba) → Laguna Main Hub. A different plan may go through SH3 (Pagsanjan) for a buyer in a farther Laguna area. If a buyer's municipality is closest to SH6, a configured plan through SH6 can be chosen without forcing SH5. The origin seller area already determines the pickup Main Hub; SH localities can also be configured around the seller-side area where an intended route requires it.

Only active plans and active checkpoints for the exact Main Hub pair are eligible. Do not infer a route by searching all locality records. If no route is configured, keep the parcel sorted and show that no plan is available. DBeaver route setup is documented in the [virtual SH routing spec](features/logistics/subhub-routing/spec.md). Coordinates give municipality-level straight-line proximity only; they cannot establish a safe or operating road/ferry route.

The selected first checkpoint is only the next planned locality. The separate mobile scanner app can report arrival/sorting at the next route point when paired. Each accepted scan authenticates a station, records the operator-confirmed event, and advances the stored route step separately from the next planned destination. The Logistics station-management page is currently hidden while the team settles the end-to-end flow; the web Logistics Center portal must not pretend that the checkpoint is a staffed facility. Courier, rider, truck, and SH scanner interfaces are mobile-only; there is no web dashboard for them.

The earlier `logistics_sub_hubs`/`orders.next_sub_hub_id` migration is a physical-site draft. The retained legacy planner and route tables use `logistics_route_checkpoints`, `logistics_route_plans`, and `logistics_route_plan_stops`; new sorts do not select them. Legacy `soc5`/`soc6` scan names do not confirm arrival at actual stations. Actual facility scan events are shared by the Logistics browser scanner and authenticated Truck Rider facility-scan endpoint; the mobile app does not use `/api/v1/sh-scanner` for new routes.

See [virtual SubHub route checkpoints](features/logistics/subhub-routing/spec.md), [truck and linehaul dispatch](features/logistics/company-truck-linehaul-dispatch/spec.md), [mobile rider API](features/courier/scan-api/spec.md), [seller shipments](features/seller/shipments/spec.md), and [schema](schema.md).

## Pickup request review (4 October 2026)

When a Seller marks an order `ready_for_pickup`, routing assigns an origin Main Hub and notifies its Logistics account. The center must verify the pending pickup request before assigning an approved pickup Rider. If the package is not ready, the center records a reason and returns the order to `preparing`; the Seller sees the reason and can mark it ready again after fixing the issue. This review is a Main Hub decision; it is not proof that the Rider physically collected the parcel. The Rider scan still records physical pickup.

A destination Main Hub may assign several eligible parcels in one delivery area to one approved Motorcycle/Van/L300 Rider in one locked transaction. Truck assignments remain the separate linehaul workflow. A new facility route requires authenticated browser scans and final-facility sorting before delivery assignment; historical virtual SH scans remain in the separate legacy API.
