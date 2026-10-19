# Main Hub Route Plan Selection

**Status:** New sorts create actual per-order facility routes; old virtual route plans retained for history
**Reviewed:** 14 October 2026

> Existing plans connect current center IDs and virtual locality checkpoints. The approved target is one Logistics network and one Main Hub per province, with routes through real Main Hubs/stations. Preserve current plans until each stop has been reviewed and mapped. See [province network and stations](../province-network-and-stations/spec.md).

New `DispatchController::markSorted` no longer calls `LogisticsRoutePlanner`. It takes up to three selected real stations in operator-chosen travel order, validates that each belongs to the origin or destination province network, and appends the destination Main Hub for a cross-province order. It stores ordered `order_facility_stops`. A local route may end at a station or use direct Main Hub delivery. Scanner arrival/sort/depart actions enforce stop order. No automatic nearest station selection or physical road feasibility check is claimed.

## Existing behavior (legacy)

Before the new facility route, `OrderRoutingService` assigned ready orders using municipality matching and `LogisticsRoutePlanner` selected virtual checkpoints. Those classes/tables remain to interpret historical rows; current ready-order matching uses unique province coverage and new sorting writes actual facility stops.

SH entries are virtual route checkpoints. Their representative municipalities help choose among configured route paths; they are not owned sites or physical addresses. No plan is guessed from all available checkpoints: a plan and its ordered stops must be explicitly configured and active. If none is valid, the parcel stays `sorted`, without an assumed route.

The additive route migration creates `logistics_route_checkpoints`, `logistics_route_plans`, `logistics_route_plan_stops`, and route-plan references on orders. Until an authorized route-management screen exists, route setup is done in DBeaver. `logistics_sub_hubs` and `orders.next_sub_hub_id` belong to an earlier physical-site draft and are no longer read by application code; the migration copies any existing checkpoint names/municipalities into the virtual registry.

## Route examples and limits

An example configured path is Manila Main Hub → SH5 (Muntinlupa waypoint) → SH6 (Calamba waypoint) → Laguna Main Hub. For a farther Laguna delivery, a different Main Hub pair may route through SH3 (Pagsanjan waypoint) before local delivery. A buyer near SH6 may match a plan that uses SH6 without SH5, if that separate path is configured and active. These examples do not preconfigure actual Vendo transport paths.

Municipality points use straight-line approximations. They cannot verify road/ferry connections, costs, schedule, capacity, or whether Vendo intends a route. Selecting a plan only saves the planned next virtual checkpoint. It does not advance parcel status, establish a physical scan, or transfer custody.

## Remaining implementation

The mobile scanner integration still needs authorized SH scan identities, checkpoint arrival/sorting events, route-step advancement, truck/manifest assignment, custody transfers, and local rider dispatch from a checkpoint. These interfaces are mobile-only and will be handled in the separate scanner repository/API work. The current Logistics web portal remains for Main Hub operations; no courier, rider, or truck browser dashboard is planned.

## Source evidence

`app/Services/LogisticsRoutePlanner.php`, `app/Http/Controllers/Logistics/DispatchController.php`, and `database/migrations/2026_09_28_190000_add_virtual_logistics_route_plans.php`.

## Related documentation

See [virtual SubHub route checkpoints](../subhub-routing/spec.md), [domain status](../../../domain-feature-status.md), and [schema](../../../schema.md).
