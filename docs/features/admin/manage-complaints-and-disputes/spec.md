# Complaints and Disputes

**Status:** Implemented core status flow  
**Reviewed:** 24 September 2026

## Current behavior

Complaints have complainant/respondent, optional order link, evidence, activity records, and an admin status update flow.

## Gaps and acceptance direction

Define resolution outcomes/refunds and role-facing conversation/notification workflow; enforce allowed transitions.

## Source evidence

`app/Http/Controllers/Admin/ComplaintController.php`, `app/Models/Complaints/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
