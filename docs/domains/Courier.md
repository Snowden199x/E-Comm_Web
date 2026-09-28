# Courier Domain

## Current state

Courier is a role and data profile in this web repository. `CourierDetail` stores personal/vehicle/license fields and a logistics-center relationship. The public rider registration API assigns new pending applicants to a unique approved active hub matching their residence by city or province. The logistics center portal can approve or reject its linked applicants and assign approved couriers to pickup or delivery orders already routed to the center. The `/logistics/get-the-app` destination is a coming-soon page. A versioned bearer-token API returns assigned work and records pickup, origin-arrival, three virtual inter-hub milestones, out-for-delivery, and delivered scans. The Flutter client is in the separate E-Comm_Mobile repository; independent delivery proof, incidents and history remain open.

## Planned responsibility

The separate rider client exposes only server-assigned orders, reuses a stable scan UUID for connection retries, and securely holds its seven-day token. Delivery evidence with private storage, failed attempts, and returns still need a server contract. See the [registration API](../features/courier/registration-api/spec.md), [scan API](../features/courier/scan-api/spec.md), and [future plan](../future-plan.md).
