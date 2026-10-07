# Main Hub Route Plan Selection

**Status:** Partial: Main Hub matching and ordered virtual route-plan selection are implemented
**Reviewed:** 28 September 2026

## Current behavior

`OrderRoutingService` assigns ready orders to approved active Main Hubs using the order's structured location and the existing municipality matching policy. When origin logistics marks a parcel sorted, `LogisticsRoutePlanner` considers active plans configured for that exact origin/destination Main Hub pair. It chooses the plan with a virtual checkpoint municipality nearest to the buyer; an empty-stop plan compares the destination Main Hub. Approximate route distance and plan priority resolve close ties. The selected plan and its first ordered checkpoint are stored on the order.

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
