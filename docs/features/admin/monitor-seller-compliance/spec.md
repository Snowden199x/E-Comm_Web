# Seller Compliance

**Status:** Implemented core review actions  
**Reviewed:** 24 September 2026

## Current behavior

Admin can browse products for review, approve/reject/warn, inspect warnings and violations, and view suspended sellers.

## Gaps and acceptance direction

Confirm all action authorization, notification, appeal, and remediation requirements; ensure rejected products cannot be sold.

## Source evidence

`app/Http/Controllers/Admin/SellerComplianceController.php`, `app/Models/Compliance/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
