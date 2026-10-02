# Truck and Linehaul Dispatch

**Status:** Partial: per-parcel Truck Rider assignment and Rider API route display are implemented; SH scans and custody progression remain unimplemented
**Updated:** 28 September 2026

## Current behavior

The web logistics flow selects a configured Main Hub route plan containing ordered virtual SH locality checkpoints. A checkpoint has a name such as Pagsanjan and a route code such as SH3; it has no exact street address or Vendo Logistics Center account. Rider registration accepts Motorcycle, Van, L300, or Truck, and Rider login returns the saved vehicle type. Motorcycle, Van, and L300 riders use local pickup/delivery assignments. After a cross-hub parcel is sorted, its origin Main Hub may assign or reassign an approved Truck Rider linked to that hub. The assignment is stored per order and included in that Truck Rider's mobile assignments with the planned route, next checkpoint, and Main Hub endpoints. The route remains a plan; assignment does not claim truck departure, checkpoint arrival, sorting, or custody transfer.

## Gaps and acceptance direction

The app does not record truck departure, vehicle manifests, checkpoint arrival, custody events, or onward dispatch. The separate mobile SH scanner app will record actual checkpoint scans using the order barcode/QR after authenticating the scanner and validating the parcel's expected route step. It must distinguish current arrival from the next planned locality and notify seller and buyer with accurate progress. The identifier alone cannot authorize a scan.

Routes depend on origin/destination Main Hubs and buyer/seller locality. For example, a Manila → Laguna plan may list SH5 (Muntinlupa) then SH6 (Calamba); a buyer in Luisiana may use SH3 named Pagsanjan if the configured path passes through that locality. Another route may skip checkpoints. Configure the ordered feasible path explicitly; representative municipality coordinates help choose among configured plans but do not prove roads, ferries, travel times, or operating permission.

Rider, Truck, and checkpoint-scanner interfaces are mobile-only. The Logistics web portal provides center-scoped per-order Truck Rider assignment; it has no Truck dashboard. Scanner routes, actor identity/permissions, route-step advancement, truck/manifest ownership, custody transfers, local rider pickup from a checkpoint, rerouting, and exception handling remain future work.

## API contract

- `GET /api/v1/rider/assignments` includes `assignment: linehaul` only for the authenticated approved Truck Rider explicitly assigned to that order by its origin Main Hub. The order must still be in a linehaul stage and retain its configured route plan.
- A linehaul item returns the origin and destination Main Hub names, ordered planned checkpoints, current route step, and next planned checkpoint. It returns no seller/buyer address or phone for linehaul work.
- Linehaul assignments are read-only in this first slice. The Rider app does not submit truck or SH scans through the existing Rider scan endpoint. Actual SH scans and route advancement belong to the separate SH scanner API/app contract.
- The owner must run `2026_09_28_200000_add_linehaul_rider_to_orders.php` before using assignment or mobile endpoints that query the new column.

## Source evidence

`app/Services/LogisticsRoutePlanner.php`, `app/Http/Controllers/Logistics/DispatchController.php`, and `routes/web.php`.

## Related documentation

See [virtual SH route checkpoints](../subhub-routing/spec.md), [route-plan selection](../hub-to-hub-routing/spec.md), and [mobile rider scan API](../../courier/scan-api/spec.md).
