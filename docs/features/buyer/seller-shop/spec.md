# Buyer Seller Shop and Shop Search

**Status:** Shop page restyled and working on current data; shop search and in-shop search/sort have views but no server support yet (Planned); owner verification pending
**Reviewed:** 6 October 2026

## Current behavior

`buyer.sellers.show` is the shop page for an approved, active Seller. It shows:

- a banner image (a solid plum block when none is uploaded), the shop picture or initial, shop name, location, and a Chat with seller button;
- four stats: products, ratings, average rating, and the month the shop joined;
- an expandable "About this shop" text when the Seller wrote a description;
- the shop's approved products in the shared product card grid, 12 per page, with an empty state when there are none.

Shop links appear on the Product page, the Cart, Checkout, My Orders, and Order detail.

## Planned in the views, waiting for the backend

- **Shop search page** (`buyer/sellers/index.blade.php`): search shops by shop name or account name, with shop cards (picture, name, location, product count) with View shop and Chat buttons, and an empty state. It needs the route `buyer.sellers.index`. The Buyer header search shows a Products/Shops selector only when that route exists, so Buyers see no change until it is added.
- **In-shop search and sort** on the shop page (search text, Latest, Price low to high, Price high to low). The bar appears only when the controller passes `$filters`.

Neither feature is implemented in the server code. See [backend needs](../../../backend-needs.md), items 2 and 3.

## Gaps and acceptance direction

- Shop search must apply the same visibility rules as the shop page (approved, not archived, active), must not search email or phone, and "account" means the user's name because there is no username field.
- The shop stats are calculated in the view. Move them into `SellerProfileController`.
- Shop cards do not show ratings or sold counts until the controller loads card metrics.
- There is no follow feature, response-rate data, or shop voucher list.

## Source evidence

`resources/views/buyer/sellers/show.blade.php`, `resources/views/buyer/sellers/index.blade.php`, `resources/views/components/buyer/layout.blade.php`, `app/Http/Controllers/Buyer/SellerProfileController.php`

## Related documentation

See [Product detail](../product-detail/spec.md), [Product search](../search/spec.md), [Browse Shop](../browse-shop/spec.md), and [domain status](../../../domain-feature-status.md).