# Seller Compliance

**Status:** Implemented core review actions  
**Reviewed:** 24 September 2026

## Current behavior

Admin can browse products for review, approve/reject/warn, inspect warnings and violations, and view suspended sellers.

## Gaps and acceptance direction

Confirm all action authorization, notification, appeal, and remediation requirements; ensure rejected products cannot be sold.

Product Review now loads and displays condition, category details, additional specifications, variant labels/SKUs/prices/stock, product video, package dimensions/weight, fragile flag, and the optional original price. Empty optional fields such as material, sizes, colors, brand, legacy weight, and shop name are omitted instead of displayed as em dashes. The queue queries only `for_review`, and approve/reject/warn endpoints also require that status, so a Seller draft cannot be reviewed through a direct POST. Owner verification is pending.

Product detail modals are limited to the viewport height and scroll internally so reviewers can reach media, category details, specifications, variants and actions on smaller screens. Seller submission and resubmission to `for_review` create a platform notification that opens this queue filtered to the product name; saving a draft does not notify Admin.

Seller suspensions issued by compliance update `account_status` while retaining registration `status = approved`, matching the user schema and active-seller gate. Compliance counts and seller lists read that account state.

## Source evidence

`app/Http/Controllers/Admin/SellerComplianceController.php`, `app/Models/Compliance/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
