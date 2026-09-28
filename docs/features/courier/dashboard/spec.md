# Courier Dashboard

**Status:** No courier dashboard planned for the web application
**Reviewed:** 28 September 2026

## Current behavior

Courier profile and center relationship exist in the web backend. Assigned-work and scan functions are provided through the rider API for the separate mobile app.

## Gaps and acceptance direction

Do not build a courier, rider, or truck web dashboard. Operational courier/rider/truck interfaces are mobile-only. The Laravel application owns authorization, assignment data, scans, order transitions, and notifications through APIs and Logistics Center workflows. The SubHub scanner app will have its own repository and documentation when supplied by the owner.

## Source evidence

`app/Models/Profiles/CourierDetail.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
