# Logistics Domain

## Purpose

A logistics center registers a business profile and reviews courier applicants affiliated with that center. The database links courier details to a center.

## Implemented scope

Center registration collects identity, business permit, address, and email OTP confirmation. Only the Logistics Center operates a logistics web portal. Its sidebar has Rider Management, Incoming Parcel Management, Parcel Sorting, Delivery assignments/monitoring, Reports, Messages, Account Management and Logout. Rider Management lists center-linked applicants and supports approve/reject with a reason for pending applications only. Incoming, Sorting, and Delivery assignments use stage-filtered, center-scoped dispatch actions. Delivery Monitoring lists active parcels and their latest known operator/status; Reports summarize current parcel statuses changed in the last 30 days. Messages remains a placeholder. Seller readiness routes to approved active origin and destination Main Hubs by municipality and approximate nearest-center distance within the same province. At origin sorting, the web app selects an active configured route plan between that exact Main Hub pair, preferring the plan with a locality checkpoint nearest to the buyer. SH3 may be named Pagsanjan for a nearby buyer area such as Luisiana; there is no exact SH address or SH Logistics Center account. The plan only records the expected route and next locality; it does not confirm arrival or custody. The destination Main Hub confirms receipt and assigns a delivery rider, who scans out-for-delivery and delivered through the mobile API. Legacy SOC-coded scans do not prove an SH locality arrival. Logistics accounts appear in Admin User Management. Rider scans notify the center and the order's buyer and seller. Courier, rider, and truck interfaces are mobile-only; the web app provides the Logistics Center portal and supporting API, not their dashboards.

The existing separate Flutter rider app submits registration and assigned-rider scans through the web API. Residence province/city codes are validated, and a unique approved active same-city or same-province center receives the pending application. No arbitrary center fallback is used.

## Critical gap

The `/logistics-preview/*` routes are a separate public click-through UI preview with hard-coded example hubs, staff and parcel counts. They have no Logistics authentication or database-backed operations and do not replace the protected Main Hub portal. Remove or secure the preview routes before exposing them as production Logistics pages.

Virtual SH route plans are partial. Route localities and ordered paths can be stored and selected by approximate proximity to the buyer. SH station management has been temporarily removed from the Logistics portal while the team finalizes the full flow; the scanner API and migration remain in code, and the destination Main Hub dispatch gate still waits for configured SH scans. Truck movement/manifests, custody handoffs, and local rider assignments from an SH remain open. A locality label does not supply a street address or prove road/ferry feasibility. Courier browser dashboards are not part of the design; courier operations use mobile APIs/apps. Independent proof of delivery and delivery exception actions are also absent. The assigned destination rider can report delivery by scan; buyer receipt confirmation remains separate. Ambiguous or missing Main Hub matches remain unresolved. Logistics-center verification documents still use public storage; rider verification uploads are private but do not yet have an authorized center download flow.

The [virtual SH routing spec](../features/logistics/subhub-routing/spec.md) records the current route model. Existing `soc5`/`soc6` values are legacy order states to replace; they do not record arrival at an SH locality or custody transfer. The separate E-Comm_MSA Flutter scanner app and Web scanner API are paused pending the agreed Logistics flow; station management is currently unavailable in the Logistics portal.

See `features/logistics/` and [future plan](../future-plan.md).
