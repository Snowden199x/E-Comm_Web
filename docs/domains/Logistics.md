# Logistics Domain

## Purpose

A logistics center registers a business profile and reviews courier applicants affiliated with that center. The database links courier details to a center.

## Implemented scope

Center registration collects identity, business permit, address, and email OTP confirmation. Center login accepts approved logistics-center and courier roles. The protected center portal has a sidebar for Rider Management, Incoming Parcel Management, Parcel Sorting, Delivery assignments/monitoring, Reports, Messages, Account Management and Logout. Rider Management lists center-linked applicants and supports approve/reject with a reason for pending applications only. Incoming, Sorting, and Delivery assignments reuse stage-filtered center dispatch actions; Delivery Monitoring, Reports, and Messages are placeholders. Seller readiness routes to a unique approved active origin and destination center by city/province. Origin assigns pickup, records arrival and sorting, and sends to destination. Destination confirms receipt and assigns delivery. Logistics accounts appear in Admin User Management. Rider scans notify the center and the order's buyer and seller.

## Critical gap

The courier browser-login branch redirects to `logistics.courier.dashboard`, but that route is not currently defined; the separate rider app should use the new versioned token API. There is no courier acceptance, physical-distance service-area allocation, truck/manifest routing, delivery completion, or delivery exception action. Ambiguous or missing hub matches remain unresolved. Verification-document URLs still use public storage and need a private-document migration.

The proposed [SOC5/SOC6 virtual checkpoint flow](../features/logistics/virtual-soc-checkpoints/spec.md) adds route-tracking milestones between real main hubs. It is documentation only; the current dispatch still uses one direct origin-to-destination hub transit state.

See `features/logistics/` and [future plan](../future-plan.md).
