# Assign Courier to Order

**Status:** Not implemented  
**Reviewed:** 24 September 2026

## Current behavior

`orders.courier_id` and courier profile relationships exist; seller pickup action requires a valid courier assignment.

## Gaps and acceptance direction

No logistics dispatch controller/route or assignment UI found. Add authorization, capacity/zone eligibility, and audit events.

## Source evidence

`app/Models/Ecommerce/Order.php`, `app/Models/Profiles/CourierDetail.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
