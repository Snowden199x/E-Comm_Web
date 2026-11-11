# Cart

**Status:** Implemented  
**Reviewed:** 6 October 2026

## Current behavior

Buyers can view cart items grouped by seller shop, add/update quantity, and remove items; data is scoped to the authenticated user. Variant products retain their selected variant ID, label, price and stock limit. The page has a "Shopping Cart" heading with a breadcrumb, a header row with a select-all checkbox, and a card per shop. Each item shows its image, name, color/size, unit price, a quantity stepper, a line total, and a visible Remove button (trash icon and the word Remove). Each item has a checkout checkbox and each shop can be selected as a group. The shop heading links to the seller page and includes a shop icon. The browser remembers the buyer's selected cart item IDs in session storage; on a first visit with no saved selection, all items start selected.

A sticky Order Summary beside the list (stacked below it on narrow screens) shows the selected item count and subtotal, a shipping line that reads "Calculated at checkout", the total, and the payment method (Cash on Delivery). Proceed to Checkout stays disabled until at least one item is selected. A **Remove selected** button above the list removes every ticked item. It asks for a second click to confirm, then sends one request per item to the existing `buyer.cart.destroy` route and reloads. An empty cart shows the shared empty state ("Your cart is empty") with a Start shopping button. The checkout button color is a flat plum (no gradient).

## Gaps and acceptance direction

Checkout rechecks selected-item ownership, product availability, stock, and changed prices on the server. Session storage is only a UI convenience; it does not authorize an item. Checkout quotes shipping from the resolved facility route and the summary shows that fee. Quantity changes and removals still reload the page. Bulk removal makes one request per item; a single endpoint taking several item IDs is listed in the [7 October frontend pull review](../../../design/2026-10-07-pulled-frontend-review.md).

The "You May Also Like" section now receives up to six active approved products in cart categories, excluding products already in the cart. Checkout locks variant rows and deducts only the selected variant. Manual UI verification remains with the project owner.

## Source evidence

`app/Http/Controllers/Buyer/CartController.php`, `app/Models/Ecommerce/CartItem.php`, `resources/views/buyer/cart.blade.php`, `public/assets/css/cart.css`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).

## 11 November 2026: 200-product cart limit

A cart holds at most **200 distinct product/variant lines** (`CartItem::MAX_LINES`). Adding a new line when the cart is full is rejected on the web cart and on `POST /api/v1/buyer/cart` with a message naming the limit; changing the quantity of a line already in the cart still works. The header badge and dashboard tile still read `99+` once the cart passes 99 lines; the 200-line cap applies to how many lines can be added, not to the badge text.
