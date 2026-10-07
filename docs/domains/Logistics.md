# Logistics Domain

## Purpose

A logistics center registers a business profile and reviews courier applicants affiliated with that center. The database links courier details to a center.

## Implemented scope

Center registration collects identity, business permit, address, and email OTP confirmation. Only the Logistics Center operates a logistics web portal. Its sidebar has Rider Management, Incoming Parcel Management, Parcel Sorting, Delivery assignments/monitoring, Reports, Messages, Account Management and Logout. Rider Management lists center-linked applicants and supports approve/reject with a reason for pending applications only. Incoming, Sorting, and Delivery assignments use stage-filtered, center-scoped dispatch actions. Delivery Monitoring lists active parcels and their latest known operator/status; Reports summarize current parcel statuses changed in the last 30 days. Messages remains a placeholder. Seller readiness routes to approved active origin and destination Main Hubs by municipality and approximate nearest-center distance within the same province. At origin sorting, the web app selects an active configured route plan between that exact Main Hub pair, preferring the plan with a locality checkpoint nearest to the buyer. SH3 may be named Pagsanjan for a nearby buyer area such as Luisiana; there is no exact SH address or SH Logistics Center account. The plan only records the expected route and next locality; it does not confirm arrival or custody. The destination Main Hub confirms receipt and assigns a delivery rider, who scans out-for-delivery and delivered through the mobile API. Legacy SOC-coded scans do not prove an SH locality arrival. Logistics accounts appear in Admin User Management. Rider scans notify the center and the order's buyer and seller. Courier, rider, and truck interfaces are mobile-only; the web app provides the Logistics Center portal and supporting API, not their dashboards.

The existing separate Flutter rider app submits registration and assigned-rider scans through the web API. Residence province/city codes are validated, and a unique approved active same-city or same-province center receives the pending application. No arbitrary center fallback is used.

## Critical gap

Virtual SH route plans are partial: route localities and ordered paths can be stored and selected by approximate proximity to the buyer, but there are no scanner API transitions for those checkpoints, truck/manifest assignments, custody handoffs, or local rider assignments from an SH. A locality label does not supply a street address or prove road/ferry feasibility. Courier browser dashboards are not part of the design; courier operations use mobile APIs/apps. Independent proof of delivery and delivery exception actions are also absent. The assigned destination rider can report delivery by scan; buyer receipt confirmation remains separate. Ambiguous or missing Main Hub matches remain unresolved. Logistics-center verification documents still use public storage; rider verification uploads are private but do not yet have an authorized center download flow.

The [virtual SH routing spec](../features/logistics/subhub-routing/spec.md) records the current route model. Existing `soc5`/`soc6` values are legacy order states to replace; they do not record arrival at an SH locality or custody transfer. The separate SubHub scanner app will be documented when its repository is provided; this repository will own its web/backend authorization and parcel state contract.

See `features/logistics/` and [future plan](../future-plan.md).
