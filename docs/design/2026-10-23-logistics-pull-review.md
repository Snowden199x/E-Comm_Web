# Logistics pull review — 23 October 2026

## Pull reviewed

The update on `dev` contains Logistics UI work from PR #77 (`f6c1a53`, including `6c3c6f3`) and the province-address registration adjustment from PR #78 (`226108e`). The reviewed diff against the prior `86dc8e4` baseline touches 45 files. This is a code and documentation review, not runtime verification.

## What arrived

### Company / Province Hub / Station preview

- A role-specific Logistics navigation configuration was added in `config/logistics_nav.php`, with Company, Province Hub, and Station sidebars.
- The Logistics shell was updated to present the three preview roles and sample alerts.
- New or refreshed Blade screens cover Company dashboard, province hub list/details, network overview, messages, reports, and account; Province Hub dashboard, stations/details, drivers, vehicles, parcels, trips/create-trip, reports, tracking, and account; and Station dashboard, pickup requests, incoming/outgoing parcels, sorting, delivery queue, rider tasks, reports, tracking, messages, and account.
- Shared tracking, messages, reports, and “coming next” views were added, along with `resources/css/logistics/workspace.css`.
- `routes/logistics_preview*.php` and associated data files provide sample hubs, stations, riders, drivers, vehicles, trips, parcels, messages, reports, and tracking results. The data is hard-coded PHP fixture data.

### Registration coverage

- The duplicate “Province covered by this account” selector was removed from Logistics registration.
- The selected address is the Logistics Company's office address; it does not claim exclusive province coverage.
- Province Hub and Station Hub creation now owns province/municipality selection and is scoped to the parent company and hub.

## What is functional today

The Company / Province Hub / Station pages were originally a click-through preview. The owner approved implementing that UI direction while preserving its layout. This session adds an authenticated `/logistics/portal/*` route group and replaces the unconditional preview include in `routes/web.php`; `/logistics-preview/*` is no longer mounted. The old route and fixture files are retained but inactive.

The initial backend foundation after the pull covered Company records, scoped dashboards, facility management, Station Rider review, Province Hub vehicles and scheduled trip creation/cancellation. Later work on the same date added primary coverage, Driver onboarding, trip/manifest transitions, station operations, manual cross-company handoff, hierarchy scanning, reports, messages, account editing and notifications. The current boundary is in the [feature spec](../features/logistics/province-network-and-stations/spec.md) and [progress log](../logs/PROGRESS-2026-10-23.md).

## Owner decision

The owner approved multiple Logistics company accounts, each able to create Province Hubs and stations, including overlapping coverage in the same province. This replaces the earlier one-account-per-province direction. UI layout should stay as pulled; backend wiring must use real scoped records and honest empty/planned states.

## Original backend checklist and current status

The accompanying [backend proposal](logistics-hub-restructure-backend-spec.md) describes a larger redesign. Its main implementation areas are:

1. **Identity, roles, and scope:** define Company Admin, Province Hub staff, Station Hub staff, Rider, and Transport Driver identities; approval/invitation lifecycle; role middleware and authorization; company and facility ownership; scoped login, queries, notifications, and records.
2. **Company, Province Hub, and Station data:** add company/hub/station relationships, manager accounts, addresses and PSGC codes, status lifecycle, uniqueness rules, and safe backfill from current Logistics centers/facilities.
3. **Rider membership and tasks:** match Riders to Station Hubs and service barangays, move Rider approval to the Station Hub, and persist pickup/delivery tasks, assignment, workload, completion, failure, and reassignment.
4. **Company transport:** add Transport Driver profiles, company vehicles, trips, trip parcels, assignment locks, departure/arrival custody, and cancellation/release rules.
5. **Routing and parcel lifecycle:** resolve seller and buyer municipalities to stations, derive local/intra-province/inter-province routes, create ordered facility stops, enforce receives/scans before sorting or delivery, and notify the correct participants.
6. **Operations APIs and screens:** replace fixture data with scoped queries and write endpoints for hub/station management, pickup verification, sorting, task assignment, trips, scans, tracking, reports, messages, account management, and Admin approval.
7. **Compatibility and rollout:** use new migrations, preserve legacy orders and scan history, backfill only unambiguous records, coordinate the separate Rider mobile API, and retire the preview routes only after authenticated replacements are ready.

This checklist was written before implementation and is retained as the original pull scope. Most web items are now implemented in code, including scoped identities/facilities, Rider tasks, Driver/trip workflows, automatic routing, scans, cross-company manifests, exceptions, proof records, reports, messages, account workflows, and notifications. The details above are historical planning text; use the current [province-network feature spec](../features/logistics/province-network-and-stations/spec.md) and [domain status](../domain-feature-status.md) for implementation status and remaining work. Rider/Transport Driver mobile integration and owner verification are still pending. Existing center-based and SH/SOC records remain during transition.

## Implementation boundary

The owner approved Company → Province Hub → Station Hub. Multiple companies may serve the same province. Preserve the pulled page layout and wire it to real, scoped records. Overlapping coverage is resolved by the Superuser's primary Station assignment. Cross-company manifests require a receiving Province Hub scan before custody is accepted.

## Verification

No tests, build, browser review, or app walkthrough were run. The owner/team should verify the visual preview and the existing live Logistics flow separately. The preview's lack of authentication is a property of sample routes, not a new authorization policy for operational data.
