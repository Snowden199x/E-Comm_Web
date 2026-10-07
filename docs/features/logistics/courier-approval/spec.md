# Courier Approval

**Status:** Partial  
**Reviewed:** 27 September 2026

## Current behavior

Approved, active logistics centers can approve or reject pending courier applications linked to their center. Review actions require authentication and a center profile; wrong-center or non-courier records are denied, and already-reviewed applications are rejected under a row lock.
Unsubmitted rejection reasons in Rider Management and the dashboard survive a same-tab reload for up to two hours. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

The separate rider app now submits applications through `POST /api/v1/rider/register`. The server matches residence to one approved, active center by city or province and creates a pending courier linked to it. The rider cannot request a center ID. Addresses without a unique match receive a validation error; no application is created.

## Gaps and acceptance direction

Authorized reviewer delivery of the now privately stored verification documents remains future work. Review decisions do not yet have a dedicated audit record.

## Source evidence

`app/Http/Controllers/Logistics/DashboardController.php`, `app/Models/Profiles/CourierDetail.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
