# Courier Domain

## Current state

Courier is a role and data profile, not a complete app in the current repository. `CourierDetail` stores personal/vehicle/license fields and an optional logistics-center relationship. The logistics center dashboard can approve or reject linked applicants. The `/logistics/get-the-app` destination is a coming-soon page. No courier dashboard, API, mobile source, assigned-delivery list, pickup flow, delivery status action, proof-of-delivery upload, incident form, or delivery history was found.

## Planned responsibility

The future courier client should only expose orders assigned to its authenticated courier, support idempotent scan/status events, capture delivery evidence with private storage, handle offline retries, and record failed attempts/returns. A versioned API and completed shipment model are prerequisites; see [future plan](../future-plan.md).
