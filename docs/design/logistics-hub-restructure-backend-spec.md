# Logistics Hub Restructure — Backend Spec

**For:** backend developer
**Written by:** frontend, based on a read of `E-Comm_Web`
**Status:** proposal, not yet implemented

> Project rules in `AGENTS.md` still apply: add **new** migrations (do not edit old ones), keep legacy data, validate and authorize on the server, log every change in `docs/logs/PROGRESS-YYYY-MM-DD.md`, and update the related specs.

---

## 1. Why this change

The code today treats **one logistics account as one province network** with **one Main Hub** and optional stations that have no login of their own. Riders belong to that account.

The target flow is a three-level hierarchy where each level has its own interface:

```text
Logistics Company (e.g. J&T)            -> Company Admin
└── Province Hub (one per province)     -> Province Hub staff
    ├── Transport Drivers + Company Trucks (company transport)
    └── Station Hub (one per municipality) -> Station Hub staff
        └── Riders (assigned per barangay)
```

Naming note: `users.is_super_admin` already means the **Vendo platform** super admin. Do not reuse it. Use a new role such as `logistics_company` for the company admin.

---

## 2. Decisions already made

| Topic | Decision |
|---|---|
| Station Hub coverage | **One Station Hub per municipality** (per company) |
| Rider coverage | Riders are assigned **per barangay** inside their Station Hub |
| Province Hub | One per province per company |
| Routing | Province Hub is used **only when a parcel moves between different Station Hubs** |
| Same-province, different stations | Always goes Station → Province Hub → Station (no direct station-to-station) |
| Multiple companies | Allowed. The old "one network per province" rule is removed |
| Station ↔ Province Hub leg | Moved by a **company Transport Driver** with a company truck/van, not by motorcycle riders (see section 6B) |
| Company driver role | **One** role, `transport_driver`, under the Province Hub. No separate "Hub Transfer Driver" and "Linehaul Driver" accounts. The **trip type** decides what the driver is doing |

### Transport types

Every physical leg of a parcel's journey has an accountable owner:

| Transport | Responsible | Vehicle |
|---|---|---|
| Seller → Station Hub | Rider (pickup task) | Rider's own vehicle |
| Station Hub ↔ Province Hub | Transport Driver (`station_transfer` trip) | Company truck/van |
| Province Hub ↔ Province Hub | Transport Driver (`inter_province` trip) | Company truck |
| Station Hub → Buyer | Rider (delivery task) | Rider's own vehicle |

### Routing cases

| Case | Example | Route |
|---|---|---|
| 1. Same Station Hub | Lucban → Lucban | Seller → Rider (pickup) → Station Hub (receive, scan, sort) → Rider (delivery) → Buyer |
| 2. Different stations, same province | Lucban → Sariaya | Seller → Pickup Rider → Origin Station → **Station Transfer** → Province Hub → **Station Transfer** → Destination Station → Delivery Rider → Buyer |
| 3. Different provinces | Lucban, Quezon → Lumban, Laguna | Seller → Pickup Rider → Origin Station → **Station Transfer** → Origin Province Hub → **Inter-Province Linehaul** → Destination Province Hub → **Station Transfer** → Destination Station → Delivery Rider → Buyer |

```text
IF origin_station = destination_station
    route_type = local
ELSE IF origin_province_hub = destination_province_hub
    route_type = intra_province
ELSE
    route_type = inter_province
```

Case 1 needs **no** company transport trip. Cases 2 and 3 need one transport trip per leg between facilities.

Full example, Lucban, Quezon → Lumban, Laguna:

```text
Seller
 ↓ Rider pickup task
Lucban Station Hub
 ↓ Transport trip (station_transfer)
Quezon Province Hub
 ↓ Transport trip (inter_province)
Laguna Province Hub
 ↓ Transport trip (station_transfer)
Lumban Station Hub
 ↓ Rider delivery task
Buyer
```

---

## 3. Accounts and registration (do this first)

Registration is the foundation because every later feature depends on who owns which hub. Today `Logistics\Auth\RegisteredUserController` makes the applicant **claim a province** (`coverage_province_code`, unique) and creates one Main Hub.

### 3.1 Roles

| Role | Scope | Created by |
|---|---|---|
| `logistics_company` | Whole company | Self-registers, approved by Vendo Admin |
| `province_hub` (staff/manager) | One Province Hub and its stations | Company Admin |
| `station_hub` (staff/manager) | One Station Hub | Province Hub |
| `courier` (rider) | One Station Hub, specific barangays | Self-registers (mobile), approved by Station Hub |
| `transport_driver` | One Province Hub | Self-registers (mobile), approved by Province Hub |

> `transport_driver` replaces the earlier `linehaul_driver` role name. It covers both station transfers and inter-province linehaul. Rename any existing `linehaul_driver` references in code and docs (keep legacy data).

Middleware replacing `EnsureActiveLogisticsCenter` must resolve the actor's scope from their account, never from a request ID. Keep the existing approved/active/not-archived checks.

### 3.2 Company registration (`/logistics/register`)

- Remove `coverage_province_code` and the province-claim check from `store()`.
- The address on the form becomes the company's **head office address**. Hubs are created after approval, not at registration.
- Keep the OTP, valid ID, business permit, Google sign-up and password rules as they are.
- On approval by Vendo Admin: create the `logistics_company` record. Do **not** auto-create a Main Hub.
- The existing migration `2026_10_14_120000_create_logistics_facilities.php` created a Main Hub per old center. Backfill those into Province Hubs under a company record. Do not delete the old data.

### 3.3 Hub and manager accounts

- Company Admin creates a **Province Hub** by choosing a province plus a **hub name**, a full **street address** and a manager (name, email, phone). The backend creates the user with a temporary password and `must_change_password = true` (these fields already exist on `users`).
- Province Hub creates a **Station Hub** by choosing a municipality (from the PSGC data) plus a **station name**, a full **street address** and a manager, in the same way.
- Store the full street address **and** the PSGC `province_code` / `municipality_code` on every facility. The codes drive routing; the address tells drivers where the hub is. Coordinates are optional (see section 4).
- Enforce uniqueness: one active Province Hub per `(company, province_code)` and one active Station Hub per `(company, municipality_code)`.
- A Station Hub's municipality must be inside its parent Province Hub's province.
- Deactivation must be blocked while unfinished parcels use the hub (same rule as the current station deactivation).

### 3.4 Rider registration (mobile API)

`Api\RiderRegistrationController::store` currently picks the center with `LogisticsCenterMatcher::forAddress(province, city)`.

- There is **one Rider role**. Registration does not ask whether the rider is a pickup or a delivery rider (see section 6A).
- Match the rider's residence **municipality** to the active **Station Hub** for that municipality. If none exists, return the existing "unavailable coverage" response.
- Store `station_hub_id` on `courier_details`.
- Station Hub staff approve or reject the rider (move the current `approveRider` and `rejectRider` actions there).
- Add the rider's service barangays (see section 4).
- Remove `Truck` from the rider `vehicle_type` list. Company transport is a separate role with its own registration (section 6B).
- A regular rider registers a personal vehicle (Motorcycle, Van, L300) as today.

---

## 4. Data model changes (new migrations)

```text
logistics_companies        new   id, user_id, business_name, status, ... (from the old center fields)
logistics_facilities       alter kind: province_hub | station_hub   (legacy: main_hub | station)
                                 + logistics_company_id
                                 + name, address (street address, required for new hubs)
                                 + province_code (province hubs) / municipality_code (station hubs)
                                 + latitude, longitude (nullable, optional in V1)
                                 + parent_id (station -> province hub)
                                 drop "one Main Hub per center" unique index (lf_one_main_hub)
                                 unique (company, province_code) for province hubs
                                 unique (company, municipality_code) for station hubs
facility_staff / users     new   link a user to exactly one facility (province or station)
courier_details            alter + station_hub_id
rider_service_areas        new   courier_id, station_hub_id, barangay_code, barangay
rider_tasks                new   see section 6A (pickup / delivery tasks, one Rider role)
transport_driver_details   new   user_id, province_hub_id, personal and license fields (section 6B),
                                 status (available | on_trip | off_duty | inactive)
company_trucks             new   id, company_id, province_hub_id, truck_code, plate_number, vehicle_type, capacity,
                                 status (available | on_trip | maintenance | inactive)
transport_trips            new   id, trip_type (station_transfer | inter_province),
                                 origin_facility_id, destination_facility_id,
                                 driver_id, truck_id,
                                 status (scheduled | in_transit | completed | cancelled),
                                 scheduled_departure_at, departed_at, arrived_at
transport_trip_orders      new   trip_id, order_id (the parcels loaded on the trip)
orders                     alter + origin_station_id, origin_province_hub_id,
                                 destination_station_id, destination_province_hub_id,
                                 route_type (local | intra_province | inter_province),
                                 shipping_barangay_code, shipping_barangay
```

Notes:

- `transport_trips` replaces the earlier `linehaul_trips` / `linehaul_trip_orders` design. Origin and destination are **facilities** (station or province hub), not only province hubs, so one table covers both trip types.
- `courier_details` no longer needs a `province_hub_id` for Truck riders, because company drivers now live in `transport_driver_details`.
- `orders.logistics_center_id` and `destination_logistics_center_id` are the old fields. Keep them for legacy orders.
- Checkout and seller profiles only store free-text barangay today. Add barangay codes so delivery queues can match riders automatically.
- `logistics_sub_hubs`, the virtual route plan tables and `sh_*` scanner tables are legacy. Leave them untouched.

---

## 5. Order routing

Replace `OrderRoutingService` and `LogisticsCenterMatcher`.

1. When the seller marks the order `ready_for_pickup`, resolve:
   - origin Station Hub from the seller's municipality,
   - destination Station Hub from the order's `shipping_city_code`,
   - each Province Hub from the station's `parent_id`.
2. Set `route_type` with the logic in section 2.
3. Generate `order_facility_stops` automatically in order:
   - local: `[origin station]`
   - intra_province: `[origin station, province hub, destination station]`
   - inter_province: `[origin station, origin province hub, destination province hub, destination station]`
4. Each hop between consecutive stops (except the single stop of a local route) is moved by a **transport trip**: `station_transfer` when one end is a Station Hub, `inter_province` when both ends are Province Hubs.
5. If a municipality has no active Station Hub, leave the order unrouted and notify the Province Hub or company (reuse the current unresolved-coverage behaviour).
6. Remove the manual "pick up to three stations at sorting" step in `DispatchController::markSorted`.

---

## 6. Who does what

| Step | Owner |
|---|---|
| Verify pickup request, assign a pickup **task** to any available rider | Origin **Station Hub** |
| Receive and sort at station | Station Hub |
| Case 1: sort locally and assign delivery rider | Station Hub (no Province Hub) |
| Create a `station_transfer` trip (Station → Province Hub) with a Transport Driver and company truck | **Province Hub** |
| Consolidate and sort for other stations or provinces | **Province Hub** |
| Create an `inter_province` trip (Province Hub → Province Hub) with a Transport Driver and company truck | Origin **Province Hub** |
| Receive the trip's parcels at the destination Province Hub | Destination **Province Hub** |
| Create a `station_transfer` trip (Province Hub → destination Station) | Destination **Province Hub** |
| Receive the transfer's parcels at the destination Station | Destination **Station Hub** |
| Assign a delivery **task** (by barangay, workload, availability) | Destination **Station Hub** |

The Province Hub controls all company trucks and Transport Drivers, so it creates and assigns every transport trip, including the station legs. Station Hubs only receive, scan and sort.

Rework `DispatchController` so every query is scoped by the actor's hub, not by `$center->id`.

---

## 6A. Riders: one role, task-based assignment

There is a single **Rider** role. Every approved rider can do both **pickup tasks** and **delivery tasks**. The Station Hub decides which tasks each rider gets, based on parcel volume, availability, workload and service area. Riders never pick their own parcels.

### Registration and approval

```text
Rider registers (personal info, service location, documents)
-> application goes to the Station Hub of the rider's municipality
-> Station Hub approves or rejects
-> approved = Active Rider under that Station Hub
```

### Task model

Today an order has separate columns: `courier_id` (pickup), `delivery_courier_id` (delivery) and `linehaul_rider_id` (truck). That forces a pickup/delivery split and makes workload and mixed routes hard. Add a task table:

```text
rider_tasks   new   id, rider_id, station_hub_id, order_id,
                    type (pickup | delivery),
                    status (assigned | in_progress | completed | cancelled | failed),
                    sequence (stop order within the rider's current route),
                    assigned_by, assigned_at, completed_at
```

- One order has one `pickup` task and one `delivery` task (a later re-attempt may add another delivery task).
- `orders.courier_id` and `delivery_courier_id` can remain as denormalized "current rider" columns, or be derived from the tasks. The existing `order_logistics_assignments` table is the audit log and should keep being written.
- The rider who picked a parcel up may also deliver it. Do **not** block that, and do not require a different rider.
- Only the Station Hub can create or change a task. Reject any rider-side attempt to claim a task.
- A rider can hold several tasks of both types at once (a mixed route), ordered by `sequence`. The Station Hub can reorder or reassign.

### Workload view (Station Hub dashboard)

Per rider, return: status (`available`, `on_route`, `off_duty`; an approved rider can also be deactivated, shown as `inactive` in the Riders tabs), open pickup tasks, open delivery tasks, total, assigned barangays.

```text
Rider    Status      Pickup  Delivery  Total
Rider A  On Route    2       6         8
Rider B  Available   0       0         0
Rider C  On Route    1       3         4
```

The assignment screen should let staff filter riders by availability, workload, service barangay and current route. Automatic suggestions are optional. The assignment itself stays a manual Station Hub decision.

### Same-station parcels still go through the hub

Even when seller and buyer share a Station Hub, the rider must **not** take the parcel from seller to buyer. The Station Hub must receive and scan it first, then sort it, then assign delivery. Required status chain:

```text
Ready for Pickup -> Rider Assigned -> Picked Up -> Received at Station Hub
-> Sorted -> Out for Delivery -> Delivered
```

The backend must reject `out_for_delivery` for a parcel that has no recorded Station Hub receive scan.

### Rider app task list

A **flat task list** is enough for now. The rider app shows every task the Station Hub assigned, pickup and delivery together, with filters **All | Pickups | Deliveries | Completed**. The rider chooses the practical order. Route optimization and a `sequence` order are a future enhancement.

- `rider_tasks.sequence` can be left out of the first version (or kept nullable).
- Keep the Station Hub-only assignment rule: the rider cannot claim tasks.

---

## 6B. Transport drivers, company trucks and trips

**Transport Driver** is a single role under the **Province Hub**. A Transport Driver never does seller pickups or buyer deliveries. Their job is moving consolidated parcels between facilities using **company-owned trucks/vans**. What they do on a given day is decided by the **trip type**, not by their account type.

This mirrors the Rider design: one Rider role receives `pickup` and `delivery` tasks; one Transport Driver role receives `station_transfer` and `inter_province` trips.

```text
Rider            -> pickup task            Transport Driver -> station_transfer trip
                 -> delivery task                           -> inter_province trip
```

| | Rider | Transport Driver |
|---|---|---|
| Works under | Station Hub | Province Hub |
| Work items | Pickup and delivery tasks | Transport trips |
| Trip / task types | `pickup`, `delivery` | `station_transfer`, `inter_province` |
| Vehicle | Own vehicle (Motorcycle, Van, L300) | Company truck/van, assigned per trip |
| Approved by | Station Hub | Province Hub |
| Assigned by | Station Hub | Province Hub |

### Registration (mobile API)

```text
Transport Driver registration
-> personal information
-> driver's license information
-> select the Province Hub
-> upload documents
-> Province Hub reviews
-> Approved / Rejected
-> Active Transport Driver
```

Fields: full name, birthday, sex, home address, contact number, email, driver's license number, license category, **license expiration date**, driver's license upload, valid ID, Province Hub.

- The driver does **not** register a truck, plate number or OR/CR (the current `courier_details` flow asks for these).
- Validate that the license is not expired, and re-check it before each trip is assigned.

### Company trucks

Trucks belong to the company and are managed by the Province Hub, separately from drivers. Vehicle types should cover both small transfer vehicles (van, L300) and large linehaul trucks.

```text
Truck TRK-001: plate ABC 1234, 10-Wheeler, 15 tons, Quezon Province Hub, Available
Truck TRK-004: plate XYZ 5678, L300 Van, 1 ton, Quezon Province Hub, Available
```

A truck is not permanently tied to a driver.

| Entity | Statuses |
|---|---|
| Vehicle | `available`, `on_trip`, `maintenance`, `inactive` |
| Transport Driver | `available`, `on_trip`, `off_duty`, `inactive` |
| Transport Trip | `scheduled`, `in_transit`, `completed`, `cancelled` |

Only `available` drivers and vehicles can be assigned to a new trip. A vehicle in `maintenance` or `inactive` cannot be assigned. Cancelling a scheduled trip releases the driver, the vehicle and the loaded parcels. A trip already `in_transit` cannot be cancelled.

### Transport trip

```text
Transport Trip = Trip Type + Origin Facility + Destination Facility
               + Driver + Company Vehicle + Parcels
```

Fields: trip ID, trip type, origin facility, destination facility, driver, company vehicle, parcels, scheduled departure, actual departure time, arrival time, status.

Trip types:

| Trip type | Direction |
|---|---|
| `station_transfer` | Station Hub → Province Hub, or Province Hub → Station Hub |
| `inter_province` | Province Hub → Province Hub |

Example:

```text
Transfer Trip #HT-001
Type: station_transfer
Origin: Lucban Station Hub
Destination: Quezon Province Hub
Driver: Juan Dela Cruz
Truck: TRK-004
Parcels: 85
```

Flow (same for both trip types):

```text
Province Hub consolidates parcels for a destination facility
-> creates a Transport Trip (status: scheduled)
-> assigns an available Transport Driver and an available company vehicle
-> parcels are loaded (transport_trip_orders)
-> driver confirms departure (in_transit)
-> arrives at the destination facility
-> destination facility receives the shipment (completed)
-> driver and vehicle become available again
```

Rules for the backend:

- Only the Province Hub that owns the driver and vehicle can create and assign a trip. For a `station_transfer`, the Station Hub end must be a child of that Province Hub. For an `inter_province` trip, the destination must be a different active Province Hub.
- Driver, vehicle and every parcel must belong to (or currently be at) the origin facility's Province Hub scope.
- A driver or vehicle cannot be on two active trips at once (lock and check inside a transaction).
- Departure moves every loaded parcel to in-transit. Arrival is confirmed by the destination facility (Station Hub or Province Hub), per parcel, using the facility scan.
- This replaces `orders.linehaul_rider_id` and the "assign Truck Rider" action (`assignLinehaulRider`). Keep the old column for legacy orders.
- Reuse the same backend logic, driver app, scanning, vehicle management and trip screens for both trip types. Do not build a second system for station transfers.

### Optional: multi-stop collection runs (future)

A truck may collect parcels from several Station Hubs on one scheduled run:

```text
Quezon Province Hub -> Lucban Station -> Tayabas Station -> Sariaya Station -> Quezon Province Hub
```

The first version can model this as several single-leg `station_transfer` trips. If a single multi-stop run is wanted later, add a `transport_trip_stops` table (trip_id, facility_id, sequence, arrived_at, departed_at). Do not block the first version on this.

---

## 7. Scans and statuses

- Keep the current design: UUID `scan_key` for idempotency, one action per stop, ownership and sequence checks (`FacilityScanWorkflow`).
- Change it so the expected sequence comes from the order's generated stops. It must accept routes of 1, 3 and 4 stops.
- In Case 1 the parcel is still received and scanned at the Station Hub, sorted there, and only then assigned to a delivery rider. There is no seller-to-buyer shortcut (section 6A).
- Current statuses (`at_sorting_center`, `in_transit_to_hub`, `at_destination_hub`, ...) are Main Hub oriented. Rename or map them (for example "at Station Hub" and "at Province Hub") so Case 1 never passes through a "destination hub" status. Keep the string column plus `Order::STATUSES` convention.
- Transport departure and arrival are recorded per trip and per parcel, tied to the assigned Transport Driver, for both `station_transfer` and `inter_province` trips.
- The rider mobile API (`planned_route.scan_target`) will need the new facility kinds. The mobile app lives in `E-Comm_Mobile`; coordinate the contract change. The Transport Driver app is the same driver app for both trip types.

---

## 8. What the frontend will build

The frontend can start from the existing `/logistics-preview/*` screens (hard-coded data). The frontend's final page list is below. Rider and Transport Driver apps are mobile-only and are not built on the web.

**UI naming:** the frontend labels the page "Vehicles" (not "Trucks") because it covers trucks, vans and L300s. The table and API can keep `company_trucks`; only the label differs.

### Company Admin

| Screen | Data needed |
|---|---|
| Dashboard | Totals: Province Hubs, Station Hubs, riders, transport drivers, company vehicles, parcels; parcels in transit, delivered, **delayed/failed**; active transport trips; recent activities; Province Hub summary (stations, riders, parcels, active trips, status) |
| Province Hubs list | Hub name, province, manager, station count, driver count, vehicle count, parcel volume, status; activate/deactivate |
| Create Province Hub form | Province list (PSGC), hub name, address, manager name/email/phone; returns validation errors |
| Province Hub details | Hub info, manager, Station Hubs, drivers, vehicles, current parcel volume, active trips, performance |
| Network Overview | Hierarchy tree: Province Hubs with their Station Hubs (read-only) |
| Parcel Tracking | See section 8A |
| Reports | See section 8A |
| Account Management | Company profile, head office information, business documents, manager profile, change password |

### Province Hub

| Screen | Data needed |
|---|---|
| Dashboard | Station count, parcels at hub, awaiting sorting, incoming/outgoing transfers, active trips by type, available/on-trip drivers and vehicles, delayed transfers |
| Station Hubs list | Station name, municipality, manager, riders, parcels, status; activate/deactivate |
| Create Station Hub form | Municipalities in the Province Hub's province, station name, address, manager name/email/phone |
| Parcel Operations (tabs Incoming, Sorting, Outgoing, History) | Parcels by stage and source (Station Hub or other Province Hub); next destination per parcel (another Station Hub in the province or another Province Hub) |
| Transport Trips (filters All, Station Transfers, Inter-Province, Scheduled, In Transit, Completed, Cancelled) | Trips with type, origin, destination, driver, vehicle, parcel count, scheduled departure, status |
| Create Transport Trip | Trip type, origin and destination facility (options depend on type), available drivers, available vehicles, parcels ready to load, scheduled departure |
| Transport Drivers (tabs Active, Applications, On Trip, Inactive) | Name, contact, province hub, license number/category/expiry, status, current trip, completed trips; approve/reject |
| Vehicles | Code, plate, type, capacity, province hub, status, current trip; add, edit, set maintenance, activate/deactivate |
| Parcel Tracking, Reports, Account | See section 8A |

### Station Hub

| Screen | Data needed |
|---|---|
| Dashboard | Pending pickup requests, available riders, riders on route, incoming transfers, received today, awaiting sorting, waiting for transfer, ready for delivery, out for delivery, delivered today, failed deliveries; rider workload table |
| Pickup Requests | Tracking number, seller, pickup address, barangay, request time, status, assigned rider; assign/reassign |
| Incoming Parcels | Parcels from riders (seller pickups) and from the Province Hub (station transfers); receive scan |
| Sorting | Received parcels with their auto-determined next step: delivery queue (local) or waiting for province transfer |
| Outgoing Transfers | Parcels waiting for a `station_transfer`, grouped by destination; once a trip exists, read-only trip ID, driver, vehicle and status |
| Delivery Queue | Parcels ready for delivery grouped by barangay with counts; select parcels to assign |
| Task Assignment | Unassigned pickup and delivery tasks; riders with availability, service barangays, pickup/delivery/total workload; assign action |
| Riders (tabs Active, Applications, On Route, Inactive) | Name, service barangays, vehicle, status, pickup/delivery/total tasks; approve/reject, activate/deactivate, manage service barangays |
| Parcel Tracking, Reports, Account | See section 8A |

The Station Hub never changes a trip's driver or vehicle, and never chooses a destination hub at sorting.

### Registration (company)

No province claim; head-office address only.

---

## 8A. Shared screens: tracking, reports, overview

These were missing from the first version of this spec. All three portals need them, each scoped to the actor's hub (Company Admin sees the whole company, Province Hub its province and stations, Station Hub its station).

### Parcel Tracking

- **Search:** by tracking number or order ID. Return nothing for parcels outside the actor's scope (authorize on the server, same rule as section 3.1).
- **Result:** the full journey as an ordered list of events: status, facility or actor, time. Example:
  `Picked Up -> Lucban Station Hub -> Quezon Province Hub -> In Transit to Laguna -> Laguna Province Hub -> Lumban Station Hub -> Out for Delivery -> Delivered`.
- Build it from the existing status history, facility scans, rider tasks and transport trips. Show the transport trip ID and trip type on each transport leg.

### Reports

Return aggregates with a date-range filter (default: last 30 days). Report types per portal:

| Portal | Reports |
|---|---|
| Company Admin | Parcel volume, delivery performance, Province Hub performance, Station Hub performance, rider performance, transport driver performance, vehicle utilization, transport trip reports, failed/delayed deliveries |
| Province Hub | Parcel volume, incoming/outgoing transfers, Station Hub performance, transport trips, transport driver performance, vehicle utilization, delayed/failed transfers |
| Station Hub | Pickup requests, completed pickups, deliveries, failed deliveries, rider performance, barangay parcel volume, incoming/outgoing parcels |

Define "delayed" once on the server (for example a parcel or trip past its expected time) and use that same rule for the dashboard counts and the reports.

### Network Overview (Company Admin)

One read-only call returning each Province Hub with its Station Hubs (name, municipality, status). No operational actions.

### Dashboard counts

Company dashboard needs: Province Hubs, Station Hubs, riders, transport drivers, vehicles, total parcels, parcels in transit, delivered, delayed/failed, active trips. Province Hub and Station Hub dashboards need the counts listed in the tables above.

---

## 9. Suggested order of work

1. Registration and accounts (section 3) plus the new roles and middleware. Nothing else can be scoped correctly without these.
2. Migrations for hubs, riders, transport drivers/trucks/trips and order columns (section 4), with backfill of existing centers.
3. Routing service (section 5).
4. Dispatch controller rework (section 6).
5. Transport trip workflow (section 6B), then scan workflow and driver/rider API (section 7).
6. Update `docs/`: `order-logistics-flow-decisions.md`, `schema.md`, the province-network spec (it currently says one network per province and no competing companies), and `domain-feature-status.md`.

---

## 10. Open questions

- ~~How does a Case 2 parcel move between a Station Hub and its Province Hub?~~ **Resolved:** by a `station_transfer` trip run by a Transport Driver with a company vehicle (section 6B).
- ~~Should the first version support multi-stop collection runs?~~ **Resolved:** no. V1 uses single-leg trips only (for example Lucban Station to Quezon Province Hub). Multi-stop runs can add `transport_trip_stops` later (section 6B).
- What should happen to existing logistics accounts that have no clean province claim?
- Can a manager have several staff accounts later, or is one login per hub enough for now?
- ~~Do hubs need a street address and coordinates?~~ **Resolved:** store the full street address plus PSGC province/municipality codes now. Coordinates are optional and can wait for maps (section 4).