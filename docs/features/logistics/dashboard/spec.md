# Logistics Center Dashboard

**Status:** Partial  
**Reviewed:** 28 September 2026

## Current behavior

Authenticated, approved, active logistics centers with a profile can list their own pending riders and counts of pending/approved/rejected applications. The Rider Management page exposes center-scoped approve/reject actions for pending applications. Delivery Monitoring and Reports are center-scoped read views for current parcel status and 30-day order counts. The portal uses a shared responsive Logistics sidebar; see [navigation](../navigation/spec.md).

## Gaps and acceptance direction

Incoming Parcel Management, Parcel Sorting, and Delivery assignments link to stage-filtered center dispatch pages. The existing assigned-rider API has transitional SOC-coded states on old flows; Logistics web views identify them as legacy and do not claim they prove an SH locality arrival. Configured virtual SH locality routes can be selected at origin sorting; scanner arrival, route progression, truck manifests/assignments, and local rider handoff from an SH remain unimplemented. Delivery Monitoring and Reports reflect current order rows only. Logistics-center verification documents still use public storage; rider verification uploads use the private local disk but have no authorized reviewer download flow. Courier/rider/truck work has no web dashboards.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `resources/views/logistics/dashboard.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
