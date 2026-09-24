# Logistics Domain

## Purpose

A logistics center registers a business profile and reviews courier applicants affiliated with that center. The database links courier details to a center.

## Implemented scope

Center registration collects identity, business permit, address, and email OTP confirmation. Center login accepts approved logistics-center and courier roles. The center dashboard lists center-linked rider applicants and supports approve/reject with a reason.

## Critical gap

The logistics dashboard route currently has no explicit `auth` middleware in `routes/web.php`; add role, approval, and center-scope middleware before treating this area as secure. The courier login branch redirects to `logistics.courier.dashboard`, but that route is not currently defined. There are no working assignment, pickup scan, sorting, hub, linehaul, shipment tracking, or delivery exception controllers.

See `features/logistics/` and [future plan](../future-plan.md).
