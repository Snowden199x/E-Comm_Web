# Buyer Seller Shop and Shop Search

**Status:** Shop page, shop search, and in-shop search/sort wired to server queries; owner verification pending
**Reviewed:** 7 October 2026

## Current behavior

`buyer.sellers.show` is the shop page for an approved, active Seller. It shows:

- a banner image (a solid plum block when none is uploaded), the shop picture or initial, shop name, location, and a Chat with seller button;
- four stats: products, ratings, average rating, and the month the shop joined;
- an expandable "About this shop" text when the Seller wrote a description;
- the shop's approved products in the shared product card grid, 12 per page, with an empty state when there are none.

Shop links appear on the Product page, the Cart, Checkout, My Orders, and Order detail.

## Search and sorting

- **Shop search page** (`buyer.sellers.index`): searches approved, active, non-archived Sellers by shop business name or account name only. The page shows approved product counts, View shop and Chat buttons, and a paginated empty state. The Buyer header has a Products/Shops search selector.
- **In-shop search and sort:** the shop page filters approved products by name and sorts by latest, ascending price, or descending price, with server-side validation and pagination.

Both use the Buyer session and the same shop visibility rule as the shop profile. Search strings are limited to 100 characters.

## Gaps and acceptance direction

- The shop stats are calculated in the view. Move them into `SellerProfileController`.
- Shop cards do not show ratings or sold counts until the controller loads card metrics.
- There is no follow feature, response-rate data, or shop voucher list.

## Source evidence

`resources/views/buyer/sellers/show.blade.php`, `resources/views/buyer/sellers/index.blade.php`, `resources/views/components/buyer/layout.blade.php`, `app/Http/Controllers/Buyer/SellerProfileController.php`

## Related documentation

See [Product detail](../product-detail/spec.md), [Product search](../search/spec.md), [Browse Shop](../browse-shop/spec.md), the [7 October frontend pull review](../../../design/2026-10-07-pulled-frontend-review.md), and [domain status](../../../domain-feature-status.md).
