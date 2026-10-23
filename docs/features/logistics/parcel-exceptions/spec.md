# Logistics Parcel Exceptions

**Status:** Web workflow implemented in code; migrations and owner verification pending.

Province and Station staff can report an issue only when an order is at their active facility, has arrived, and has not departed. The system stores the issue type, required details, reporter, facility, and status. An order can have only one open exception at a time; the order row is locked while creating or resolving one.

An open exception blocks hierarchy scans and trip departure for the parcel. Staff at the reporting facility can resolve it by resuming normal handling or, after sorting, inserting an unvisited active facility owned by the same Company before the next unvisited route stop. A detour is rejected if an active trip is already scheduled from the current facility; cancel that trip before changing the route. Cross-company routes cannot use the same-company detour action and require the Company handoff workflow.

Reporting and resolution create order timeline events and notify the Buyer, Seller, and Company account. Reports include facility exception counts. Rider task issue reports are a separate workflow: the assigned Rider can mark only their active RiderTask failed, add a reason/details timeline event, and alert Station staff, Company, Seller and Buyer. This does not assert that the parcel has returned to a facility or change order custody/status. The failed task appears in Station Task Assignment; the station must verify custody before assigning another Rider. The failed Rider is blocked from further scans. Claims/refunds, returns, photos of damage, and automated rerouting remain out of scope.

Routes: `GET/POST` and `PATCH resolve` under the role-scoped `/logistics/portal/{province|station}/exceptions` routes. Migration `2026_10_23_190000_add_logistics_operations_followups.php` creates `logistics_parcel_exceptions` and must be applied by the owner.
