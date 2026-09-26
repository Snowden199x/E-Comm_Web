# Courier Domain

## Current state

Courier is a role and data profile, not a complete app in the current repository. `CourierDetail` stores personal/vehicle/license fields and an optional logistics-center relationship. The logistics center portal can approve or reject linked applicants and assign approved couriers to pickup or delivery orders already routed to the center. The `/logistics/get-the-app` destination is a coming-soon page. A versioned bearer-token API now returns assigned work and records pickup, origin-arrival, and out-for-delivery scans. There is no courier dashboard or mobile source here; delivery confirmation, proof, incidents and history remain open.

## Planned responsibility

The future courier client should only expose orders assigned to its authenticated courier, reuse the same scan UUID for offline retries, and securely hold its short-lived token. Delivery evidence with private storage, failed attempts, and returns still need a server contract. See the [scan API](../features/courier/scan-api/spec.md) and [future plan](../future-plan.md).
