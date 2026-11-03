# Transport Driver Applications

**Status:** Implemented in code; owner migration and verification pending
**Reviewed:** 30 October 2026

## Current behavior

A Transport Driver applies from the Driver app and chooses **who reviews the application**:

| Apply to | Reviewed by | Station Hub | Work scope |
|---|---|---|---|
| Station Hub only | The chosen Station Hub | Approves or rejects | Station to station |
| Province Hub only | The chosen Province Hub | Read-only, linked by residence | Hub to hub trips |
| Both | The chosen Province Hub only | Read-only, no approval needed | Hub to hub trips |

The Station Hub never approves a driver that also applied to the Province Hub. In that case it
sees the driver's details and documents read-only with the status *Waiting for Province Hub
approval*, so the two hubs cannot both decide on one application.

### Registration

`POST /api/v1/transport-drivers/register` validates the email OTP proof, the licence and the valid
ID, then stores one `transport_driver_details` row:

- `apply_to` — `station`, `province`, or `both` (defaults to `province` for older app builds).
- `province_hub_id` — the chosen Province Hub, or the parent Province Hub of the chosen Station Hub.
- `station_hub_id` — the chosen Station Hub for a station-only driver; for province and both
  drivers, the Station Hub that covers the applicant's municipality, the nearest Station Hub by
  municipality coordinates when none covers it, or null when nothing can be determined.
- `approved_by` / `approved_at` — written by the reviewing hub on approval only. A rejection keeps
  its reason on the user account so the applicant sees it in the app.

`App\Services\StationHubResolver` performs the residence match from the PSGC province and city codes
the app sends. It never attaches a driver to an arbitrary facility: a municipality match wins, then
the nearest Station Hub measured between municipality centres, then a Station Hub in the chosen
Province Hub network. A station-only application requires a Station Hub inside a Province Hub
network (`parent_id` of kind `province_hub`).

The application notifies the reviewing hub's manager and links to the right page: *Drivers* in the
Station Hub for a station-only application, *Transport Drivers* in the Province Hub otherwise. A
`both` application also sends the linked Station Hub a read-only notice.

`GET /api/v1/transport-drivers/province-hubs` returns the open Province Hubs **and** the open
Station Hubs (with municipality, parent hub and staffing), so the app can offer the right list for
the chosen scope. `GET /api/v1/transport-drivers/locations` and
`.../locations/{cityCode}/barangays` serve the PSGC picker.

### Hub review

- Station Hub: *Drivers* page (`lgp.station.drivers`) lists drivers whose `station_hub_id` is that
  station. Approve / Reject is enabled only for `apply_to = station`; province and both rows are
  read-only. Routes: `lgp.station.drivers.review`, `.status`, `.document`.
- Province Hub: *Transport Drivers* page (`lgp.province.drivers`) lists drivers whose
  `province_hub_id` is that hub. Approve / Reject is enabled only for `apply_to` of `province` or
  `both`; station-only rows are read-only.
- Both review endpoints lock the driver and user rows, refuse an expired licence, and refuse a second
  decision. They set `approved_by` and `approved_at` and notify the driver.
- A station-only driver is not offered in *Create trip*: it cannot take an inter-hub trip.

### Sign-in and status

- `POST /api/v1/transport-drivers/login` signs in an approved driver whose reviewing hub and company
  are active. A valid applicant who is not approved yet receives `403` with `status` of `pending` or
  `rejected`, the reviewing hub name and the rejection reason, so the app can explain the state
  instead of reporting a wrong password.
- `POST /api/v1/transport-drivers/application-status` (throttled) returns the same state for an
  email without a session, so the applicant can check the decision before the first sign-in.

### Session profile and password recovery

- `GET /api/v1/transport-drivers/me` returns the signed-in driver in the same shape as `login`
  (`driver`, `province_hub`, `station_hub`, `reviewing_hub`, `company`). The app calls it on start-up
  to restore a stored token, so a revoked token, a suspension or an expired licence returns `401`
  and the driver is sent back to sign-in instead of into a stale session.
- `POST /api/v1/transport-drivers/password/otp/send` emails a six-digit code to a
  `transport_driver` account only. It answers the same way for an unknown email, so the endpoint
  does not disclose which addresses are registered.
- `POST /api/v1/transport-drivers/password/otp/verify` exchanges the code for a 15-minute
  `reset_token`, and `POST /api/v1/transport-drivers/password/reset` sets the new password and
  revokes every existing token for that driver. The OTP and proof cache keys are separate from the
  Rider ones, so a Rider code cannot be used to reset a driver password.
- Recovery does not change the application decision: a driver still waiting for approval can reset
  the password and will still be told the application is pending at sign-in.

## Gaps and acceptance direction

- Migration `2026_10_30_120000_add_application_scope_to_transport_driver_details.php` has been
  applied to the local database; other environments still need to run it.
- The residence match prefers the applicant's municipality; "nearest" is a municipality-centre
  distance, not a road or GPS distance. A driver may still be re-linked by the Province Hub.
- A rejected applicant cannot re-apply with the same email (the address is unique); clearing the
  old application or allowing a resubmission is future work.
- The Driver app's trip screens still read demo data; only registration, approval status, sign-in,
  the session profile and password recovery call the API.
- A token rejected by `/me` is cleared on that device, but the server cannot revoke a token on a
  device that never comes back online beyond the seven-day expiry.

## Source evidence

`app/Http/Controllers/Api/TransportDriverRegistrationController.php`,
`app/Http/Controllers/Api/TransportDriverApiController.php`,
`app/Http/Controllers/Api/TransportDriverPasswordResetController.php`,
`app/Services/StationHubResolver.php`, `app/Services/TransportDriverAccessService.php`,
`app/Http/Controllers/Logistics/PortalController.php`,
`resources/views/logistics/station/drivers.blade.php`,
`resources/views/logistics/province/drivers.blade.php`,
`database/migrations/2026_10_30_120000_add_application_scope_to_transport_driver_details.php`.
