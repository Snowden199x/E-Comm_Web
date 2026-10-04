# Cart

**Status:** Implemented  
**Reviewed:** 4 October 2026

## Current behavior

Buyers can view cart items grouped by seller shop, add/update quantity, and remove items; data is scoped to the authenticated user. Variant products retain their selected variant ID, label, price and stock limit. The page has a "Shopping Cart" heading with a breadcrumb, a header row with a select-all checkbox, and a card per shop. Each item shows its image, name, color/size, unit price, a quantity stepper, a line total, and a trash button. Each item has a checkout checkbox and each shop can be selected as a group. The shop heading links to the seller page and includes a shop icon. The browser remembers the buyer's selected cart item IDs in session storage; on a first visit with no saved selection, all items start selected.

A sticky Order Summary beside the list (stacked below it on narrow screens) shows the selected item count and subtotal, a shipping line that reads "Calculated at checkout", the total, and the payment method (Cash on Delivery). Proceed to Checkout stays disabled until at least one item is selected. An empty cart shows a short message with a Start Shopping link.

## Gaps and acceptance direction

Checkout rechecks selected-item ownership, product availability, stock, and changed prices on the server. Session storage is only a UI convenience; it does not authorize an item. Checkout currently records a shipping fee of 0, so the summary does not quote one. Quantity changes and removals still reload the page.

The "You May Also Like" section now receives up to six active approved products in cart categories, excluding products already in the cart. Checkout locks variant rows and deducts only the selected variant. Manual UI verification remains with the project owner.

## Source evidence

`app/Http/Controllers/Buyer/CartController.php`, `app/Models/Ecommerce/CartItem.php`, `resources/views/buyer/cart.blade.php`, `public/assets/css/cart.css`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).