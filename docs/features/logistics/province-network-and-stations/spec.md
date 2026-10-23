# Province Logistics Network and Stations

**Status:** Logistics web hierarchy and operational workflows implemented in code; owner migrations and runtime verification pending; mobile Rider/Driver integration remains partial
**Decision recorded:** 23 October 2026

**Current decision note (23 October 2026):** The owner approved the pulled Company / Province Hub / Station Hub structure. Multiple companies may operate in the same province; each company can have one active Province Hub per province and one active Station Hub per municipality under that Province Hub. This supersedes the prior one-network-account-per-province rule. See the [pull review](../../design/2026-10-23-logistics-pull-review.md) and [implementation spec](../../design/logistics-hub-restructure-backend-spec.md).

## Agreed direction

- A Logistics Company account may manage Province Hubs in multiple provinces. Multiple companies may cover the same province.
- Each company may have one active Province Hub per province. A Province Hub may configure named Station Hubs in its province; each active municipality may have one Station Hub per company.
- Province Hub and Station Hub managers use their own accounts, scoped to the facility assigned by the Company or Province Hub manager.
- Riders belong to a Station Hub and are reviewed by that Station Hub. Transport Drivers and company vehicles belong to a Province Hub.
- Do not create virtual `SH5`, `SH6`, or `SH3` route stops. Configure routes using real, named Main Hubs and stations.
- A town that a truck merely passes through is not a station and does not require a scan. Record a scan when a parcel arrives at, is sorted at, or is handed off at an operational stop.
- Keep the pulled interface layout and visual language. Replace sample fixtures and local-only form actions with server data and scoped backend actions; show honest empty states where data does not exist.

This is a school-project network model. It supports multiple company accounts with overlapping provincial coverage. The Superuser selects one primary Station Hub per municipality when coverage overlaps; a sole active Station Hub is selected automatically. A route within one company proceeds through scheduled company trips. A route across companies requires a manual Company handoff from the origin Province Hub and a receiving Province Hub scan to accept it.

## Registration and account scope

Keep the address/PSGC list complete for all roles. Logistics Company registration collects its office address and does not reserve a province. Province Hub creation selects its province, and Station Hub creation is restricted to a municipality inside its parent Province Hub. Multiple companies may have active facilities in one municipality. Without a primary assignment for an overlap, matching remains unresolved. If the primary station is inactive, the Superuser must replace it; the matcher does not silently switch companies.

Enforce one active Province Hub per company/province and one active Station Hub per company/municipality. Creation is locked and checked server-side. Company coverage is not exclusive between companies.

The Company owns Province Hubs; each Province Hub owns its Station Hubs. Province and Station staff accounts are linked to exactly one facility and requests must resolve scope from the authenticated membership.

Rider applications should be assigned to an eligible Station Hub using the Rider's PSGC residence and that station's service barangays. The Rider's residence address remains a normal PSGC address. If no eligible station exists, show a clear unavailable-coverage state instead of assigning the Rider arbitrarily.

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

1. Seller marks the order ready. Vendo resolves the Seller address to a covered Company/Province Hub/Station Hub and sends the pickup request to the selected network.
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

- `2026_10_23_130000_create_logistics_network_portals.php` is an additive migration. It creates one Company per existing Logistics Center, links facilities, remaps legacy `main_hub` and `station` kinds, removes the old unique province claim, and adds manager membership, Rider service area/task, transport driver/vehicle/trip, and order-route schema. Historical rows are retained. The owner must run the migration.
- Logistics registration no longer reserves a province. Admin approval/rejection updates the linked Company status. The Company account remains the existing `logistics_center` role; Province Hub and Station Hub staff have scoped facility memberships.
- Authenticated `/logistics/portal/*` dashboards and facility pages load database-backed Company/Province Hub/Station data. Company Hubs and Province Stations can be created and activated/deactivated with server-side ownership and municipality/province validation; manager setup links are sent by email. Station Rider applications are scoped to that station, and review/status updates persist. Province Hub vehicles can be added, edited, and set available/maintenance/inactive with company/hub scope and server validation.
- `/logistics-preview/*` is no longer mounted. The hierarchy portal opens the laptop/browser scanner in a modal and records hierarchy-scoped scans. Existing `/logistics/facilities` and legacy SH records remain for older orders.
- The Superuser Logistics Coverage page assigns a primary Station Hub by PSGC province/municipality and retries unresolved ready orders after a change. Seller-ready routing resolves Seller and Buyer stations, builds ordered real-facility stops and notifies the origin Station. New Rider registration uses the same matcher; legacy Truck Rider registration remains accepted for mobile compatibility pending the Driver app transition.
- Station Hub operations show real pickup, task, incoming, sorting, outgoing and delivery lists. Staff can verify/assign pickup, return an unready parcel to Seller with a reason and assign final delivery to an approved local Rider. Province Hub parcel lists and transport trip manifests show real records. Trip departure requires each manifest parcel's origin departure scan; trip completion requires destination arrival scans. Driver and vehicle become available again on completion.
- Scanner arrival records arrival and sorting together. Departure requires a scheduled trip for the next same-company leg. A unique scan key makes retries idempotent. Seller, Buyer and Logistics notifications follow accepted scans.
- For cross-company orders, the origin Company initiates a handoff after its Province Hub sorts. A receiving Province Hub scan accepts it. The handoff remains pending until that scan; choosing a destination company alone does not transfer custody.
- Company, Province and Station portals have scoped operational reports, persisted Company-to-facility conversations, account profile/password editing and user-specific notifications. Operational tables count orders created in 7/30/90 days, tasks, trips and facility activity. The Company Reports page also summarizes shipping settlements by settlement date: shipping fees on settled orders, Rider/Driver accrual lines credited at the Company’s facilities, and the Logistics remainder for routes handled by that Company alone. Cross-company Logistics remainder is shown separately as unallocated and is not counted as either Company’s revenue. Route-review settlements are reported separately and create no Rider/Driver credits. Financial figures are accrual/pool records, not completed payouts; Admin’s 10% product commission is separate and receives no shipping share. Delivery SLA is not calculated.
- Transport trips can store an expected arrival time. Cross-company handoffs can be grouped into a Company/Province Hub manifest with an approved available Driver, available vehicle and ETA; origin departure records status events, and destination Province Hub scans accept parcels individually. The Driver and vehicle are released after all manifest parcels are accepted. Delayed trips and handoffs appear in the Company dashboard and Province tracking view.
- Province and Station staff can report an exception while a parcel is at their active facility. The exception pauses further scans and trip departure until same-facility staff resolve it; same-company routes can insert an unvisited facility detour after sorting. Open exceptions and actions are included in reports and notify the Seller, Buyer and Company account.
- Assigned Rider delivery completion for new facility-routed orders now requires `POST /api/v1/rider/delivery-proofs`: recipient name, private photo (JPG/PNG/WebP, up to 5 MB), stable scan UUID, and optional note. The mobile app collects and submits proof. The API writes proof and `delivered` scan in one transaction. Buyer receipt confirmation remains a separate step. Authorized Buyer, Seller and in-scope Logistics staff can view the private proof through the order page. This is a recipient/photo record, not GPS or identity verification.
- The Rider app can report an issue on its own active pickup/delivery task. The server pauses the task as `failed`, records an order timeline note, and notifies the Station, Company, Seller and Buyer. Station Task Assignment displays the issue. It does not claim the parcel returned to the Station; staff must verify custody before assigning a replacement Rider.
- Approved Company Transport Drivers can sign in from the Rider mobile app's separate Transport Driver entry, load only their own active trips, request departure after all manifest origin scans, and close arrival only after all manifest destination scans. The APIs use a distinct `transport-driver:trip` token ability and do not expose Buyer/Seller contact addresses.
- Company users can request an email change using their current password and a 30-minute link sent to the new address; the current session must be authenticated to confirm. Company users can submit replacement ID/business-permit files to private storage. Admin can review the file and approve or reject it; approval replaces the current Logistics Center document path. Province/Station managers can edit their display name, phone and password but do not have a separate email/document workflow.
- The Logistics notification bell polls the signed-in user’s unread notifications while the tab is visible. This is short polling, not websocket/push delivery. CSV reports include scoped orders, station activity, trips, cross-company manifests, and (for Company accounts) the shipping-settlement summary.

## Remaining limits

- Owner must run the migrations and manually verify the workflow. No automated tests, build or browser/app walkthrough were run by the agent. Reports still lack SLA percentages and PDF/Excel exports. Financial reports show accruals and Logistics pool values only; there is no payout, reversal, or cross-company remainder allocation. Notification updates use polling and require owner verification.
- Legacy virtual SH route plans and scanner tables remain for historical compatibility. No automatic mapping from an old SH label to a new facility occurs. Legacy orders retain the old destination receipt gate; new facility routes use the final-facility scan.
- Company coverage selection is implemented through the Superuser primary-station page. No production camera or end-to-end acceptance was run by the agent; the owner tests after migration.

## Implemented migration sequence

Run `php artisan migrate` to apply pending migrations, including `2026_10_23_130000` through `2026_10_23_210000`, plus any earlier unrun Logistics migrations. Migrations `190000` to `210000` add trip/handoff ETAs and manifests, parcel exceptions, delivery-proof metadata, email-change requests, and replacement-document review. Do not run `migrate:fresh` or delete legacy route/scan records.

## Open decisions before full implementation

- How Admin explicitly retires/replaces an approved network or resolves duplicate legacy province accounts.
- Which real facilities are used for each route, including the handoff owner for cross-province routes.
- Whether stations only receive/sort/hand off parcels, or can also act as final-mile dispatch points.
- Whether a station's physical address and coordinates are mandatory or whether a named locality is enough for the first demo.
- Whether staff receive individual accounts later, or use a station-paired scanner device after the first authenticated prototype.

## Related

See [Logistics domain](../../../domains/Logistics.md), [order and logistics decisions](../../../order-logistics-flow-decisions.md), [schema overview](../../../schema.md), [legacy virtual SH routing](../subhub-routing/spec.md), [legacy SH scanner API](../sh-scanner-api/spec.md), and [truck/linehaul dispatch](../company-truck-linehaul-dispatch/spec.md).
