# SH Scanner Pairing and Checkpoint API

**Status:** Web API implemented in code; Logistics station page temporarily paused pending the finalized Logistics flow; owner migration/device verification pending  
**Updated:** 12 October 2026

## Scope

Station management at `/logistics/scanners` is temporarily unavailable: its Logistics web routes and sidebar entry have been removed while the team finalizes the end-to-end Logistics flow. The pairing/API implementation and database migration remain in code for later review. Do not pair stations or treat the scanner flow as ready for team use. Before the pause, the page created a station for one active SH checkpoint on a route involving that Main Hub, showed a five-minute single-use QR, and allowed refresh/revocation. No public scanner account or password exists.

The Flutter Vendo Scanner client is in the separate `E-Comm_MSA` repository. SHs are virtual locality labels, not registered Logistics Centers or exact warehouse addresses. A scan is an authorized operator report of arrival and sorting at that SH, not GPS proof, Truck departure, or a custody transfer.

## API contract

- `POST /api/v1/sh-scanner/pair` with a 64-character code from `vendo-scanner-pair:v1:<code>`. The server hashes the code at rest, consumes it under a station row lock, and returns a 30-day bearer token and station/checkpoint summary. Expired, revoked, or used codes fail.
- `GET /api/v1/sh-scanner/session` returns the paired station and checkpoint after token validation.
- `POST /api/v1/sh-scanner/lookup` with `tracking_number` returns a minimal confirmation when the parcel is assigned to a Truck Rider, is at `sorted` or `in_transit_to_hub`, and this station is exactly its next active route checkpoint. It exposes no Buyer contact/address.
- `POST /api/v1/sh-scanner/scans` with `tracking_number` and UUID `scan_key` confirms arrival/sorting. The server locks the station and order, validates active route plan, exact next stop, origin/destination Main Hub pair and current status, writes one `sh_checkpoint_scan_events` row and a timeline note, then advances `route_step` and `next_route_checkpoint_id` once. The first accepted SH scan changes `sorted` to `in_transit_to_hub`; later SH scans retain that status. Repeating the same UUID at the same station and parcel returns the original event with `duplicate: true`; a conflicting UUID use or wrong stop fails with no route advance.
- `GET /api/v1/sh-scanner/scans` returns the station's 20 most recent accepted scans, with tracking and checkpoint only.
- `POST /api/v1/sh-scanner/logout` revokes the station token. Offline local signout cannot guarantee server revocation; Logistics can revoke the station in Web.

Every protected endpoint checks the bearer token's hash, expiry, station revocation, active checkpoint, and approved/active owner Main Hub. Tokens and pairing codes are never stored in plain text. Scans require an active configured route; legacy Rider `soc5`/`soc6` actions do not count as SH arrivals.

## Order and notification behavior

The accepted SH event writes an `order_status_events` note showing the confirmed locality and the next **planned** stop. Buyer, Seller, origin Main Hub, and destination Main Hub receive a scoped notification; the next locality is not labeled as reached. Destination Main Hub receipt is blocked while `next_route_checkpoint_id` or `route_step` still points to an unscanned SH.

The destination Main Hub still manually confirms receipt and assigns the delivery Rider. A direct route with no SH stops, Truck departure/arrival, manifest, custody proof, and final-mile pickup from SH are not supplied by this scanner API. The older SOC-coded Rider endpoint remains a separate legacy path and needs replacement later.

Migration: `2026_10_12_130000_create_sh_scanner_stations_and_events.php`. This pause does not roll back the migration or delete existing station/event data. The API endpoints remain registered, and the destination Main Hub dispatch gate still waits for configured SH route scans. Revisit both behaviors when the Logistics workflow is finalized. No migration command or app test was run for this pause.
