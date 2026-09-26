# Logistics Center Dashboard

**Status:** Partial  
**Reviewed:** 26 September 2026

## Current behavior

Authenticated, approved, active logistics centers with a profile can list their own pending riders and counts of pending/approved/rejected applications. The Rider Management page exposes center-scoped approve/reject actions for pending applications. It now uses the shared responsive Logistics sidebar; see [navigation](../navigation/spec.md).

## Gaps and acceptance direction

Incoming Parcel Management, Parcel Sorting, and Delivery assignments link to stage-filtered center dispatch pages. Manual hub send/receipt and the assigned rider scan API are available; truck manifests and delivery completion are still unavailable. Verification-document URLs still use public storage and need a private-document migration.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `resources/views/logistics/dashboard.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
