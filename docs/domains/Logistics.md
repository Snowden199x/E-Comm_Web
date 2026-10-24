# Logistics Domain

## Province network

One Logistics Company may operate Province Hubs across multiple provinces. Multiple companies can serve the same province. Each company can create one active Province Hub per province and Station Hubs under that Province Hub. Province and Station managers have scoped accounts; Riders belong to Station Hubs. A town passed through without receiving or handing off a parcel is not a station and requires no scan.

Company registration records its office address and does not reserve a province. Company Admins create Province Hubs, and Province Hub managers create Station Hubs. The additive migration links existing Logistics Centers to company records and converts legacy Main Hub/station facility kinds while retaining rows and history.

## Current web operations

The authenticated `/logistics/portal/*` hierarchy portal has database-backed Company/Province/Station dashboards and facility management. Province Hubs review Transport Driver applications, manage vehicles and scheduled trip manifests, and record trip departure/arrival. Station Hubs review Riders, assign pickup/delivery work, and return unready pickup requests to Sellers. The hierarchy scanner uses the browser camera to receive/sort or depart a parcel at its planned stop. Cross-company handoffs can be grouped into manifests with Driver, vehicle and ETA; receiving hubs accept custody by scanning each parcel. Province/Station staff can report and resolve parcel exceptions, with scans paused until resolution. New facility-routed deliveries require recipient name and private photo evidence through the Rider API. Scoped reports/CSV, messages, account/email/document editing, notifications and parcel lists are connected to server data. The previous `/logistics/facilities` scanner remains for legacy orders.

Seller-ready routing and new Rider registration consult the Admin's primary Station Hub for an overlapping municipality. A sole active station is selected automatically. Orders across different companies have a manual Company-to-Company handoff; the receiving Province Hub accepts custody by scanning the parcel. Historical orders retain legacy routing. The mobile Rider app has not yet been updated for this contract; Transport Driver mobile login and trip execution are pending.

## Pulled Company / Province Hub / Station screens

The October 2026 Company, Province Hub, and Station pages were originally hard-coded UI previews. The active hierarchy routes now use authenticated, facility-scoped data for the implemented slices. Unimplemented routes display the planned-state view instead of claiming to persist actions. The old preview route files remain in the repository but are no longer included by `routes/web.php`.

The [backend restructure spec](../design/logistics-hub-restructure-backend-spec.md) documents the approved target hierarchy and implementation boundary. It is only partially implemented. See the [23 October pull review](../design/2026-10-23-logistics-pull-review.md).

## Remaining work and legacy data

The scanner records an authenticated operator report, not independent proof of vehicle movement or custody. Delivery proof stores a recipient name and image but does not verify GPS or identity. Company Reports now summarizes shipping settlement amounts and accrued Rider/Driver credits; these are not payouts, and cross-company Logistics remainder is still unallocated. Reports do not calculate SLA and export CSV only. Notification refresh is short polling. The separate Rider app must consume the new Station task, trip, exception and delivery-proof contracts; Driver mobile login/trip execution is also pending. Owner migrations and end-to-end verification are pending.

The old `/logistics-preview/*` route and fixture files remain in the repository but are no longer mounted from `routes/web.php`. Legacy virtual SH route tables, SOC states, and the paused `/api/v1/sh-scanner` API remain for historical orders; preserve those rows and scans when migrating. Rider and Transport Driver interfaces remain mobile-only and belong in the separate E-Comm_Mobile repository.

See [province network and stations](../features/logistics/province-network-and-stations/spec.md), [order and logistics decisions](../order-logistics-flow-decisions.md), [schema](../schema.md), and [future plan](../future-plan.md).
