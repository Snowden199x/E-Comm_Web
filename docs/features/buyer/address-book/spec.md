# Buyer Address

**Status:** Partial  
**Reviewed:** 6 October 2026

## Current behavior

Buyer profile stores province, municipality, barangay, street, and ZIP; checkout formats a default address from profile data. On the 6 October checkout page this registered address is the first and default choice. A Buyer can add another address in a dialog for the current order; it is held in `sessionStorage` for the tab (two hours), can be removed, and is submitted through the existing `shipping_*` fields. It is not saved on the server.

## Gaps and acceptance direction

No multi-address book is saved on the server: extra addresses disappear on another browser or device, and there is no way to edit or set a new default. A `buyer_addresses` table, Buyer-scoped routes, and server-side revalidation are needed ([backend needs](../../../backend-needs.md), item 4). Current registration stores one combined street field and sets house number null.

## Source evidence

`app/Models/Profiles/BuyerDetail.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).