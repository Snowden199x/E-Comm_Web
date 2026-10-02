# Courier Domain

## Current state

Courier is a role and data profile in the web backend. `CourierDetail` stores personal/vehicle/license fields and a logistics-center relationship. The mobile registration API supports Motorcycle, Van, L300, and Truck applicants and assigns each pending applicant to a unique approved active Main Hub matching residence by city or province. Motorcycle, Van, and L300 riders use local pickup/delivery assignments; Truck riders alone receive Main Hub linehaul assignments. Logistics Center staff approve/reject linked applicants and assign approved riders through the Logistics Center web portal. A versioned bearer-token API returns assigned work and records the current rider scan actions. Courier, rider, and truck work has no web dashboard; those operational interfaces are mobile-only. The existing Flutter rider app is in the separate E-Comm_Mobile repository. Physical SH5/SH6/SH3 handoffs and a dedicated SubHub scanner app are not implemented; the scanner app's docs will be handled when its repository is provided. Independent delivery proof, incidents and history remain open.

## Planned responsibility

The existing rider mobile client exposes only server-assigned orders, reuses a stable scan UUID for connection retries, and securely holds its seven-day token. Physical SubHub receiving/sorting actors and truck handoff require web backend/API support plus mobile-only operational apps; no courier/truck web interface is planned. Delivery evidence with private storage, failed attempts, and returns still need a server contract. See the [registration API](../features/courier/registration-api/spec.md), [scan API](../features/courier/scan-api/spec.md), and [future plan](../future-plan.md).
