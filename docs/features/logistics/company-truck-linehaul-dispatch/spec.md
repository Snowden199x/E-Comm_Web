# Truck and Linehaul Dispatch

**Status:** Actual facility routes and assigned Truck visibility implemented; inter-network custody remains unimplemented
**Reviewed:** 14 October 2026

> Existing linehaul assignments expose planned virtual SH routes and do not prove departure, arrival, or custody. Future plans must use actual Main Hubs/stations and define which province network owns each cross-province handoff. See [province network and stations](../province-network-and-stations/spec.md).

New sorted cross-province orders store actual facility stops and can be assigned to an approved Truck Rider of the origin account. `GET /api/v1/rider/assignments` exposes these stops in the existing `planned_route.checkpoints` shape and the next unarrived facility. The old route-plan response remains for historical orders. The Truck API remains read-only; web facility scans are operator reports and do not create Truck manifests or a signed inter-network custody transfer.

## Historical virtual route behavior

Older orders may have configured Main Hub route plans containing ordered virtual SH locality checkpoints such as Pagsanjan/SH3. They are names, not staffed facilities. Rider registration accepts Motorcycle, Van, L300, or Truck, and login returns vehicle type. Motorcycle, Van, and L300 riders use local pickup/delivery assignments. New cross-province sorts use actual facility stops instead; either route type can be assigned to an approved Truck Rider linked to the origin Main Hub. The assignment is included in that Rider's mobile route list. Assignment itself does not claim truck departure, arrival, sorting, or custody transfer.

## Gaps and acceptance direction

The Rider app does not record Truck departure, vehicle manifests, custody events, or onward dispatch. The separate mobile SH scanner and Web API code can record operator-confirmed checkpoint arrival/sorting reports after station authentication and route-step validation, but Logistics station management is currently paused while the team finalizes the workflow. Do not treat the scan flow as ready for team operation. The identifier alone cannot authorize a scan.

Routes depend on origin/destination Main Hubs and buyer/seller locality. For example, a Manila → Laguna plan may list SH5 (Muntinlupa) then SH6 (Calamba); a buyer in Luisiana may use SH3 named Pagsanjan if the configured path passes through that locality. Another route may skip checkpoints. Configure the ordered feasible path explicitly; representative municipality coordinates help choose among configured plans but do not prove roads, ferries, travel times, or operating permission.

Rider, Truck, and checkpoint-scanner interfaces are mobile-only. The Logistics web portal provides center-scoped per-order Truck Rider assignment; it has no Truck dashboard. Scanner station permissions and route-step advancement remain in the separate SH API code, but the Logistics station page/routes are paused. Truck/manifest ownership, custody transfers, local rider pickup from a checkpoint, rerouting, and exception handling remain future work.

## API contract

- `GET /api/v1/rider/assignments` includes `assignment: linehaul` only for the authenticated approved Truck Rider explicitly assigned to that order by its origin Main Hub. The order must still be in a linehaul stage and retain actual facility stops or a historical route plan.
- A linehaul item returns the origin and destination Main Hub names and ordered actual facilities (or legacy checkpoints), plus the next unarrived stop. It returns no seller/buyer address or phone for linehaul work.
- Linehaul assignments are read-only in this first slice. The Rider app does not submit truck or SH scans through the existing Rider scan endpoint. Actual SH scans and route advancement belong to the separate SH scanner API/app contract.
- The owner must run `2026_09_28_200000_add_linehaul_rider_to_orders.php` before using assignment or mobile endpoints that query the new column.

## Source evidence

`app/Services/LogisticsRoutePlanner.php`, `app/Http/Controllers/Logistics/DispatchController.php`, and `routes/web.php`.

## Related documentation

See [virtual SH route checkpoints](../subhub-routing/spec.md), [route-plan selection](../hub-to-hub-routing/spec.md), and [mobile rider scan API](../../courier/scan-api/spec.md).
