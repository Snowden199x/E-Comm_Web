# Logistics Domain

## Province network

One approved Logistics account represents one province, including its single Main Hub and all real operating stations. For example, the Laguna account manages the Laguna Main Hub and every Laguna station. A town passed through without receiving or handing off a parcel is not a station and requires no scan.

Logistics registration selects province coverage separately from the business/Main Hub address. Pending and approved accounts reserve that province through a unique database claim; rejection releases a pending claim. The ordinary PSGC address list remains complete for Buyers, Sellers, Riders, and business addresses. Backfill assigns a claim only where an existing province has one eligible account. Ambiguous legacy accounts need Admin resolution.

## Current web operations

The protected Logistics portal has Dashboard, Hubs & Stations, Rider Management, Incoming Parcels, Parcel Sorting, Delivery Assignments and Monitoring, Reports, Notifications, Account Management, and Logout. Messages remains a placeholder. Hubs & Stations shows clickable Main Hub/station records; the account can add and manage its own stations without creating separate station logins. The selected facility opens a browser-camera scanner modal with QR/Code 128 decoding and manual tracking entry.

Seller-ready orders and Rider applications now resolve to the unique approved active province account. The origin Main Hub verifies pickup and assigns an approved local Rider. Sorting saves an explicit ordered route through selected actual stations; cross-province routes pass through the destination Main Hub before any farther destination station. The origin account may assign an approved Truck Rider, whose mobile API receives the route read-only. Logistics operators scan origin arrival and planned facility arrival/sort/departure. The server checks account/facility ownership, order stage, stop sequence, active facility, and retry key before writing events and Seller/Buyer/Logistics notifications. The final facility must sort before a local Rider is assigned. Buyer confirmation remains separate from Rider delivery reporting.

## Remaining work and legacy data

The scanner records an authenticated operator report, not independent proof of vehicle movement or custody. Truck manifests, cross-network handoff acceptance, explicit Main Hub-to-local-station transport assignment, exception/reroute handling, and independent proof of delivery remain open. Admin screens for duplicate legacy claims and province replacement are not yet built. The separate Rider app must present the new `dispatch_facility` field when a delivery starts at a station. Owner migration, Vite build, and end-to-end testing are pending.

The public `/logistics-preview/*` routes remain hard-coded examples. Legacy virtual SH route tables, SOC states, and the paused `/api/v1/sh-scanner` API remain for historical orders; new sorts do not select virtual stops and new facility routes reject SOC scans. Preserve those rows and scans when migrating. The Logistics web portal owns the new camera scanner; Rider and Truck interfaces remain mobile-only.

See [province network and stations](../features/logistics/province-network-and-stations/spec.md), [order and logistics decisions](../order-logistics-flow-decisions.md), [schema](../schema.md), and [future plan](../future-plan.md).
