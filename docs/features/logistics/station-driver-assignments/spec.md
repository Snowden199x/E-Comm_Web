# Station Hub driver assignments

**Status:** Implemented in code (2 November 2026). Owner migration and device verification pending.

## Actors and entry points

| Actor | Route | Screen |
|---|---|---|
| Station Hub manager / staff | `GET lgp.station.trips` | `resources/views/logistics/station/trips.blade.php` (sidebar: Pickups and deliveries → Driver Assignments, below Delivery Queue) |
| Station Hub manager / staff | `POST lgp.station.trips.store` | Assign form on the same page |
| Station Hub manager / staff | `PATCH lgp.station.trips.{cancel,depart,arrive}` | Trip actions, handled by the existing `PortalController` trip methods |
| Transport Driver | `GET /api/v1/transport-drivers/trips` | Driver app trip board (unchanged) |

The Drivers page links a ready driver straight into the form with that driver chosen (`lgp.station.trips?driver={id}`).

## Behaviour

- The Station Hub assigns **one of its linked Transport Drivers** (a driver whose `transport_driver_details.station_hub_id` is this Station Hub) to a trip. A driver linked to another Station Hub cannot be assigned: the lookup is scoped and returns 404.
- The Station Hub also names **where the driver must go**: its Province Hub, or another active Station Hub under the same Province Hub. The assignment is stored as a `transport_trips` row (`trip_type` = `station_transfer`) with `origin_facility_id` = this Station Hub and `destination_facility_id` = the chosen hub.
- Parcels come from the existing trip eligibility rule (`sorted` at this station, next stop is the destination, no open exception, not already on another scheduled/in-transit trip). At least one parcel is required, because departure and arrival are gated on per-parcel facility scans.
- Choosing a destination hub loads that hub's checklist. Each row shows the tracking number, order number, buyer, delivery address (barangay, municipality, province) and when it was sorted here, with a checkbox and a *Loaded / Not loaded* state. Rows whose delivery municipality and province match the chosen hub are listed first, carry a **Delivers in this hub** pill and a green edge, and **Select the N for {hub}** ticks only those; the rest are marked **Passes through**, because the hub is only a stop on their route. The list recalculates whenever the destination changes and every eligible parcel starts ticked.
- A vehicle must belong to the Province Hub and be `available`.
- The driver must be `available`, hold an unexpired licence on the departure date, and have an approved, active account. The licence and availability are re-checked inside the transaction against the departure date.
- Creating the assignment sets the driver and vehicle to `on_trip`. Cancelling releases both; recording arrival (by the destination hub) releases the driver, the vehicle and closes the trip. The service-side rules stay in `TransportTripWorkflow`, so the Station Hub cannot bypass the departure/arrival scan requirements.
- The driver learns about the assignment from the Driver app board: `TransportDriverApiController::trips()` already scopes by `driver_id`, and its payload carries the destination hub name, municipality and province. No new driver-app API is needed.

## Authorization

Both route groups use `EnsureLogisticsPortalRole::role:station`, so the facility is taken from the signed-in staff membership, never from the request. Every facility, driver and vehicle lookup is scoped to that Station Hub or its Province Hub.

## Known gaps

- No notification is sent to the driver when a Station Hub assigns a trip; the assignment appears on the next app refresh. Push notifications are not implemented.
- The trip board shows up to 60 trips involving this station, with no paging or history filter beyond status tabs.
- "Delivers in this hub" matches the delivery municipality and province **by name**, because the hub stores its address name and the order its shipping names. An order whose only difference is an alternate spelling would show as passing through even though it ends there; the parcel is still loadable.
- The trip API still carries no route distance.
- Station Hub trips use the same `station_transfer` type as Province Hub transfers, so reports cannot separate the two by type alone.
