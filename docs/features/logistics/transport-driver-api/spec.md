# Transport Driver API

**Status:** Implemented in code under `/api/v1/transport-drivers`; the Flutter screens live in the separate `E-Comm_Mobile/vendo_rider` repository under `lib/driver/`. Owner device verification pending.

**Reviewed:** 15 November 2026

The Transport Driver app talks only to this API. The routes are in `routes/api.php` under the `v1/transport-drivers` prefix and are handled by `App\Http\Controllers\Api\TransportDriverApiController`, the registration and password-reset controllers, `RiderEmailOtpController` (shared email codes) and `RiderEarningsController@transportDriver`. The web owns applications, hub approval, trip assignment, parcel state and notifications; the app never writes trip data of its own.

## Authentication

- `POST /login` `{email, password, device_name}`: a valid password on an approved `transport_driver` account returns a Sanctum token (`transport-driver:trip`, 7 days) plus the profile payload below. A wrong password answers 401. A correct password on a pending, declined or inactive application answers 403 with `status` (`pending` / `rejected` / `inactive`), `apply_to`, `reviewing_hub` and `rejection_reason`, so the app can name the hub that is reviewing it.
- `POST /google` `{credential, device_name}`: the same checks and the same response shape through a Google ID token. Google never creates an application — an email with no driver account answers 404 — and the pending/rejected answer is identical to the password login.
- `POST /logout`: revokes only the current token.
- `POST /password/otp/send|verify|reset`: emailed six-digit codes for a driver who cannot sign in; a successful reset revokes the stored tokens.
- `POST /email/otp/send|verify`: the shared email verification used by driver registration.
- `POST /register`: the driver application, with the apply-to choice (Station Hub, Province Hub, or both) deciding the reviewer.
- `GET /province-hubs`, `GET /locations`, `GET /locations/{cityCode}/barangays`, `POST /application-status`: public registration and status helpers. `province-hubs` is the Step 4 picker: `companies` (`id`, `name` of every existing approved Logistics Company whose logistics-center account is approved and active — the full set of companies, not only those that already own a hub), `province_hubs` and `station_hubs` (each carrying `logistics_company_id`, so the app narrows the hub lists to the chosen company), and the three `apply_to` choices. A company or hub is listed only while the company is approved and its logistics-center account is approved and active.
- `POST /register` accepts the optional `logistics_company_id` the picker sends; when present it must be the company that owns the chosen hub (422 otherwise).

Every protected route runs `auth:sanctum` plus `EnsureApprovedTransportDriverToken`, which re-checks the token ability, the driver approval and the active Province Hub membership on each request.

## Profile payload

`GET /me`, `/login` and `/google` share one payload: `driver` (id, name, email, phone, apply-to and its label, status, licence number/category/expiry, vehicle type, plate, station-to-station flag), `province_hub`, `station_hub`, `reviewing_hub` and `company`. The app maps a driver the same way after signing in and after restoring a saved token.

## Trips

- `GET /trips`: the board — trips assigned to this driver with status `scheduled` or `in_transit`, in-transit first, at most 50. Each trip carries its code (`TRP-00042`), origin and destination facilities, schedule and actual times, vehicle, `parcel_count` and the manifest as tracking numbers plus an `origin_scanned` / `destination_scanned` flag per parcel. No buyer names, addresses, prices or COD amounts leave this endpoint.
- `GET /trips/history`: the trips this driver already closed (`completed`), newest arrival first, at most 50, in the same payload shape — including `parcel_count` and the full manifest — so the app can still show the parcels that were loaded on a trip after it is closed. Kept apart from `/trips` because the board only carries open work, so a finished trip never disappears without a record.
- `POST /trips/{trip}/scan` `{tracking_number | tracking_numbers[], scan_key}`: records the load scans at the origin facility. One label may carry a whole container (up to 100 tracking numbers). Answers with `recorded`, `duplicates`, `unknown`, `not_routed` and the updated trip, so the app takes the server's counts.
- `POST /trips/{trip}/depart` and `POST /trips/{trip}/arrive`: move the trip and answer with the updated trip. A trip cannot leave until every manifest parcel carries its origin scan; a refusal comes back as a 409 with the server's own reason.

A trip is read or changed only by the driver it is assigned to and only inside that driver's logistics company; anything else answers 404.

## Earnings

`GET /earnings` returns the driver's accrued credits through `RiderEarningsController@transportDriver`, the same ledger shape the Rider app uses. The app displays the amounts and never computes them; closing a trip reloads the ledger.

## Notes and gaps

- The board never invents trips: with no assigned `scheduled` or `in_transit` trip the app shows the empty state and says the hub has not assigned a trip to that device yet.
- Destination scans belong to the receiving hub's station scanner; the driver app shows the counts but cannot scan them.
- Mileage and route distance are not in the API, so the app shows *Not shared by the hub* instead of a number.
- Trip history is capped at 50 with no date filter or paging.
