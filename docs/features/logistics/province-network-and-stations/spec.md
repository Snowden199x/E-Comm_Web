# Province Logistics Network and Stations

**Status:** Province claims, Main Hub/station management, per-order facility stops, and browser scanner implemented in code; owner verification pending  
**Decision recorded:** 14 October 2026

## Agreed direction

- One Logistics account represents the Logistics network for one province. For example, the Laguna account manages Logistics operations throughout Laguna.
- Each province network has exactly one Main Hub in the first version. The account is the province-wide network owner; the Main Hub is its principal operating location.
- A network can configure multiple named stations under its Main Hub. Stations are actual operational stops where parcels may be received, sorted, held, or handed to another vehicle or Rider.
- Do not create virtual `SH5`, `SH6`, or `SH3` route stops. Configure routes using real, named Main Hubs and stations.
- A town that a truck merely passes through is not a station and does not require a scan. Record a scan when a parcel arrives at, is sorted at, or is handed off at an operational stop.
- The Company/Hub preview is a visual starting point only. Its sample Company view should become the province network workspace after it is backed by real accounts, facilities, and orders.

This is a school-project network model with one Logistics operator per province. It does not currently support competing Logistics companies with overlapping provincial coverage.

## Registration and account scope

Keep the address/PSGC list complete for all roles. A Logistics applicant's **business address** is where their business is located; it must not be removed from the address API. Add a distinct **province coverage** selection for the network the account will operate. A province already claimed by a Logistics network should be unavailable in this coverage selector only.

The application must enforce province uniqueness on the server and in the database so simultaneous registrations cannot claim the same province. Recommended claim lifecycle for implementation: reserve the province when an application is submitted; release it if Admin rejects the application; retain it for the approved network, including while temporarily suspended. Releasing or replacing an approved network requires an explicit Admin operation so active Riders, orders, routes, and scan history are not orphaned. This claim lifecycle is a recommendation to implement; approval/replacement UI is not in code.

The approved province account owns one Main Hub and its stations. Do not create a separate user login for every station in the first version. Main Hub account access must remain authenticated and server-scoped; the account may only manage its own network and facilities.

Rider applications should be assigned to the unique approved network for the Rider's residence province. The Rider's residence address remains a normal PSGC address. If no approved network exists for that province, show a clear unavailable-coverage state instead of assigning the Rider to an arbitrary province.

## Facility and route model

Target relationships:

```text
Province Logistics account / network
└── one Main Hub
    ├── Station (real operating stop)
    ├── Station (real operating stop)
    └── configured routes through eligible facilities
```

Each facility needs a stable ID, name, kind (`main_hub` or `station`), province, municipality, active state, and network ownership. Store a street address and coordinates when a stop has a physical facility. The displayed locality name alone is not proof of a staffed facility, parcel storage, or scan.

Routes are explicit ordered lists of real facilities, not municipality-proximity guesses. A route can skip stations that do not handle that parcel. For cross-province delivery, a route may include the destination province's Main Hub or a participating station. The parcel must not be marked arrived at a facility merely because that facility is the next planned stop.

Cross-province operations require a handoff record identifying the sending and receiving networks, the facility, time, assigned vehicle/Rider, and accepted parcel set. The exact network that owns linehaul assignment and both sides' confirmation needs a later workflow decision. Keep this as an open implementation detail; do not imply the current Truck assignment is a completed inter-network transfer.

## Target parcel workflow

1. Seller marks the order ready. Vendo resolves the Seller's province to its unique approved Logistics network and sends the pickup request to that network's Main Hub.
2. Main Hub verifies the pickup request and assigns an approved pickup Rider. The Rider scans pickup; the Seller and Buyer receive the order update.
3. Pickup Rider scans arrival at the origin Main Hub. Logistics sorts the parcel and selects a configured route made from actual facilities. For a route with facility stops, scan departure from the origin Main Hub before the first stop can receive it. Assign a Truck Rider first for a cross-province route.
4. At a planned station or destination Main Hub, one parcel scan records arrival and sorting together. Scan departure separately when an intermediate stop hands the parcel to the next vehicle or facility. A truck passing through a locality without unloading or transferring parcels does not trigger a station scan.
5. The destination province's Main Hub receives and sorts the parcel. It assigns a local Motorcycle/Van/L300 Rider, or routes it through one of its stations if the buyer's area requires that stop.
6. The local Rider scans **Out for delivery** and then delivery. Buyer receipt confirmation remains a separate action before completion/rating.

Each accepted scan creates one timeline event and scoped Seller/Buyer/Logistics notifications. Reject wrong-network, wrong-facility, wrong-sequence, inactive-facility, and duplicate state-changing scans. The parcel's next planned facility is not an arrival event.

## Scanner direction

The first web scanner uses the laptop's browser camera from the authenticated Logistics portal. The QR and Code 128 barcode on Seller labels both contain the tracking number. ZXing decodes either format and submits it automatically; manual tracking entry remains available when camera permission is denied and submits on Enter. Laravel derives the next valid step from the parcel's assigned facility route, verifies facility ownership and route sequence, and records the scan without a confirmation click. Station/Main Hub arrival also marks the parcel sorted at that stop. Camera access requires HTTPS in deployment; `localhost` is suitable locally.

Open the scanner in a focused modal over the current Logistics page so staff can scan without losing their place in the workflow. The modal follows Vendo's existing visual language with a clear camera preview, selected facility, concise parcel/route context, visible permission/loading/result states, and a prominent X close button. Keep it within the viewport without modal or page scrolling, including on smaller screens. Keep controls usable by keyboard; stop the camera stream when the modal closes. Do not show a successful scan until the server confirms it.

The parcel barcode/QR identifies the parcel; it does not authorize a scan. The POST route requires an approved active Logistics session, verifies ownership of the selected facility, and uses a unique UUID scan key for retries. A later device-pairing flow may bind a laptop/tablet to one station and allow revocation, but a separate scanner app or purchased handheld scanner is not required for this web prototype.

Keep scan processing online for the initial project version. Offline scan queues, GPS evidence, and automated truck tracking are future work and must not be implied by the scan UI.

## Current implementation

- Logistics registration selects a province coverage code in addition to its business/Main Hub address. Pending and approved claims reserve the province. A unique database index prevents concurrent new claims. Admin rejection releases the code. The Main Hub address must resolve to a city in that province. Existing unique-province accounts are backfilled; ambiguous duplicate-province legacy accounts receive no claim and need Admin resolution before new routing or station creation.
- One Main Hub facility is created per existing or new center. Its owner ID is unique. The authenticated `Hubs & Stations` page lists clickable Main Hub/station cards; the owner can add, edit, deactivate, and scan at its stations only. Deactivation is blocked while an unfinished parcel route uses that station.
- Seller-ready order and Rider registration matching now select the approved active account by unique province claim. Seller and Rider address catalogs remain unchanged.
- Origin sorting creates an ordered per-order route through up to three selected real stations. Cross-province routes place origin stations before the destination Main Hub and destination stations after it, so a remote buyer can receive a parcel from a station beyond that hub. Direct routes omit stations. Local routes may end at a station. The Main Hub scanner records origin arrival/departure; each route stop scan records arrival and sorting together, with a later departure scan at intermediate stops. The first route stop requires a recorded origin Main Hub departure. Each scan checks ownership, active facility, order stage, previous stop departure, and unique scan key. Seller, Buyer, and owning Logistics account receive facility-specific notifications; order status events preserve the scan location.
- The Truck Rider assignment API exposes actual facility stops and a server-derived `planned_route.scan_target`. Only the assigned Truck Rider can use `POST /api/v1/rider/facility-scans` to scan an active origin Main Hub or a facility stop on that order. The API reuses the facility workflow, which derives arrival/sort/departure from current route state. Transitional SOC scans reject new facility routes; old virtual routes and SH scan history remain readable. A destination parcel on a new route must be scanned at its final facility and sorted there before local Rider assignment. The Rider assignment response includes the final dispatch facility.
- `/logistics-preview/*` still uses public hard-coded sample data. The operational network pages are the authenticated `/logistics/facilities` routes, using the live Vendo Logistics layout.

## Remaining limits

- A scan is a signed-in Logistics operator or assigned Truck Rider report; it is not independent proof of truck movement or physical custody. Cross-network manifest acceptance, vehicle load lists, and disputes are still open.
- A local station route has no separate transport assignment from Main Hub to station. Local delivery Riders receive the station address in the API assignment, but the separate mobile UI must present that new field. Staff/device identity remains one province account for this first version.
- Legacy virtual SH route plans and scanner tables remain for historical compatibility. No automatic mapping from an old SH label to a new facility occurs. Legacy orders retain the old destination receipt gate; new facility routes use the final-facility scan.
- The province replacement/retirement flow and Admin screen to resolve duplicate legacy province claims are not implemented. No production camera or end-to-end acceptance was run by the agent; the owner tests after migration and asset build.

## Implemented migration sequence

Run `php artisan migrate` to add the province claim, `logistics_facilities`, `order_facility_stops`, and `facility_scan_events`. Compile and deploy Vite assets for the scanner JS and ZXing dependency. Inspect any legacy Logistics account without a claim before approving it or using it for new routing. Do not run `migrate:fresh` or delete legacy route/scan records.

## Open decisions before full implementation

- How Admin explicitly retires/replaces an approved network or resolves duplicate legacy province accounts.
- Which real facilities are used for each route, including the handoff owner for cross-province routes.
- Whether stations only receive/sort/hand off parcels, or can also act as final-mile dispatch points.
- Whether a station's physical address and coordinates are mandatory or whether a named locality is enough for the first demo.
- Whether staff receive individual accounts later, or use a station-paired scanner device after the first authenticated prototype.

## Related

See [Logistics domain](../../../domains/Logistics.md), [order and logistics decisions](../../../order-logistics-flow-decisions.md), [schema overview](../../../schema.md), [legacy virtual SH routing](../subhub-routing/spec.md), [legacy SH scanner API](../sh-scanner-api/spec.md), and [truck/linehaul dispatch](../company-truck-linehaul-dispatch/spec.md).
