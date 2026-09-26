# Assigned Rider Scan API

**Status:** Backend implemented; separate rider client pending  
**Updated:** 26 September 2026

The seller's 4 × 6 inch label encodes only `orders.tracking_number` in both Code 128 and QR. A code is an identifier, not an authorization credential. The future rider client must authenticate the approved courier account and submit the decoded tracking number; no rider dashboard or camera interface is added to this web repository.

## Contract

- `POST /api/v1/rider/login`: `email`, `password`, `device_name`; returns a seven-day bearer token with only `rider:scan` ability, rider identity, and approved center. Invalid credentials and inactive/unapproved accounts share a generic error. Login is rate limited.
- `GET /api/v1/rider/assignments`: bearer token required. Returns at most 100 current pickup or delivery orders assigned to that rider and their approved center; includes tracking number, status, assignment kind, hub names, and only the address/contact for that assignment's pickup or delivery stop. No other riders' orders are returned.
- `POST /api/v1/rider/scans`: bearer token required. Body: `tracking_number`, `scan_type`, and a client-generated UUID `scan_key` retained for offline retries. Supported transitions: `pickup` (`ready_for_pickup → picked_up`, assigned origin pickup rider); `origin_arrival` (`picked_up → at_sorting_center`, same rider/center); `out_for_delivery` (`assigned_to_rider → out_for_delivery`, assigned destination delivery rider). The response has order ID, tracking number, new status, center, server scan time, and `duplicate` flag. The same key for the same rider/type returns the original event without another status change or notification; a conflicting key or stale stage returns 409. Other riders/centers get 403.
- `POST /api/v1/rider/logout`: revokes the current bearer token.

All protected routes require an actual Sanctum personal access token as well as approval, active account, and approved active center membership on every request. Scans lock the order row, write `order_scan_events` and `order_status_events`, and update the order in one transaction. The Order status observer creates seller and buyer notifications naming the relevant hub/city; the scan workflow also notifies the center owner. The order timeline exposes the scan note to the authorized buyer and seller. The timestamp and center are server verified; no GPS coordinate or delivery proof is asserted.

Two additive migrations create `personal_access_tokens` and `order_scan_events`; the owner runs migrations. HTTPS is required on the deployed domain. The later mobile client must store the token securely, show assigned work only, send one stable scan key per physical scan, and retry the same key after a connection failure. Delivery completion, failed attempts, proof of delivery, token/device management UI, and broader offline reconciliation are later work. No tests or walkthroughs were run by the agent per the owner's testing rule.

The [proposed virtual SOC5/SOC6 flow](../../logistics/virtual-soc-checkpoints/spec.md) adds planned courier scans after the origin-hub scan to advance the parcel through SOC5 and SOC6 toward the buyer-area hub. Those scan types, leg assignments and destination-receipt confirmation are **not** in this API contract yet. A virtual SOC scan will represent a route milestone, not proof of arrival at a physical SOC site.
