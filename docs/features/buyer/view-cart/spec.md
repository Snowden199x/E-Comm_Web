# Cart

**Status:** Implemented  
**Reviewed:** 26 September 2026

## Current behavior

Buyers can view cart items grouped by seller shop, add/update quantity, and remove items; data is scoped to the authenticated user. Each item has a checkout checkbox, each shop can be selected as a group, and the sticky summary can select all. The summary shows the selected item count and total, and checkout remains disabled until at least one item is selected. The shop heading links to the seller page and includes a shop icon. The browser remembers the buyer's selected cart item IDs in session storage.

## Gaps and acceptance direction

Checkout rechecks selected-item ownership, product availability, stock, and changed prices on the server. Session storage is only a UI convenience; it does not authorize an item. Manual UI verification remains with the project owner.

## Source evidence

`app/Http/Controllers/Buyer/CartController.php`, `app/Models/Ecommerce/CartItem.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
