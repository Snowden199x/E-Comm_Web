# Buyer Dashboard and Storefront Shell

**Status:** Dashboard data, parent-category browse, cart recommendations and variant commerce wired in code; owner verification pending
**Reviewed:** 4 October 2026

## Current behavior

Every Buyer page that uses `<x-buyer.layout>` shows a purple header: Vendo logo, product search, Messages, Cart (with item-count badge), Notifications (existing bell and polling), and an account menu with My Orders, Account Management, and Logout. The Messages control uses the white-tinted `messages-icon.svg` asset for contrast on the purple header. There is no Buyer sidebar. A category strip under the header lists Home, All Categories, and every top-level category. Hovering (or tapping, on touch screens) opens a subcategory menu: top-level categories on one side and round tiles for their subcategories on the other, each linking to `buyer.products.index?category_id=`. Tile images are optional files under `public/images/buyer/subcategories/<category>/<subcategory>.png`; a missing file shows a colored letter tile. The layout reads categories directly from the `categories` table.

The Buyer Dashboard (`buyer.dashboard`) shows an announcement banner, a scrollable Categories row, and a "Recommended for You" grid of product cards. The banner reads `$announcements` (or a single `$announcement`) and shows a plain welcome message when none is passed. With several announcements it crossfades and pauses on hover or focus. Category images are optional files at `public/images/buyer/categories/<slug>.png`; the slug is the lowercase category name with "&" as "and" and apostrophes dropped.

Product cards are shared (`buyer/partials/product-card.blade.php`). Each card has an Add to Cart button. Products without colors or sizes post to `buyer.cart.store` and update the header cart badge and a toast in place through the existing Alpine AJAX plugin. Products with colors or sizes open a quick-select sheet for color, size, and quantity first. Rating, sold count, original price, and shop name appear only when the data is available on the product; out-of-stock products show a disabled button.

## Backend behavior (4 October 2026)

`DashboardController` now passes up to five newest Buyer-audience published or due scheduled announcements. `is_active` is not used for due scheduled rows because the Admin form leaves it false until publishing. The product grid loads 18 approved products from approved active sellers, with shop details, published-review averages and sold quantities from delivered/completed orders.

Parent-category browse includes directly assigned products and products in child categories. Cart recommendations use six approved products from cart categories, excluding cart products. Buyer product detail now lists stored variants. A variant choice is required before adding a variant product to cart; product cards take Buyers to the detail page for this choice. Cart and checkout use variant prices, and checkout locks variant rows, checks stock, stores the variant on order items, and deducts the selected variant's quantity. Legacy simple products and color/size choices remain supported.

The optional `compare_at_price` column exists, but the Seller form has no input for it yet. Shipping quotation remains future work; shipping stays zero at checkout. Header category caching is still optional. Owner browser/device verification and migration are pending.

## Source evidence

`resources/views/components/buyer/layout.blade.php`, `resources/views/buyer/dashboard.blade.php`, `resources/views/buyer/partials/product-card.blade.php`, `resources/views/buyer/partials/quick-add.blade.php`, `resources/css/buyer/layout.css`, `app/Http/Controllers/Buyer/DashboardController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the [Buyer domain page](../../../domains/Buyer.md), [Browse Shop](../browse-shop/spec.md), [Cart](../view-cart/spec.md), and [feature implementation guide](../../../feature-implementation-guide.md).
