# Buyer Address

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Buyer profile stores province, municipality, barangay, street, and ZIP; checkout formats a default address from profile data.

## Gaps and acceptance direction

No multi-address book is implemented. Current registration stores one combined street field and sets house number null.

## Source evidence

`app/Models/Profiles/BuyerDetail.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
