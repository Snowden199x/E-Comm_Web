# Buyer Dashboard and Storefront Shell

**Status:** Partial — Blade views are redesigned; controller data for several parts is not wired yet  
**Reviewed:** 4 October 2026

## Current behavior

Every Buyer page that uses `<x-buyer.layout>` shows a purple header: Vendo logo, product search, Messages, Cart (with item-count badge), Notifications (existing bell and polling), and an account menu with My Orders, Account Management, and Logout. There is no Buyer sidebar. A category strip under the header lists Home, All Categories, and every top-level category. Hovering (or tapping, on touch screens) opens a subcategory menu: top-level categories on one side and round tiles for their subcategories on the other, each linking to `buyer.products.index?category_id=`. Tile images are optional files under `public/images/buyer/subcategories/<category>/<subcategory>.png`; a missing file shows a colored letter tile. The layout reads categories directly from the `categories` table.

The Buyer Dashboard (`buyer.dashboard`) shows an announcement banner, a scrollable Categories row, and a "Recommended for You" grid of product cards. The banner reads `$announcements` (or a single `$announcement`) and shows a plain welcome message when none is passed. With several announcements it crossfades and pauses on hover or focus. Category images are optional files at `public/images/buyer/categories/<slug>.png`; the slug is the lowercase category name with "&" as "and" and apostrophes dropped.

Product cards are shared (`buyer/partials/product-card.blade.php`). Each card has an Add to Cart button. Products without colors or sizes post to `buyer.cart.store` and update the header cart badge and a toast in place through the existing Alpine AJAX plugin. Products with colors or sizes open a quick-select sheet for color, size, and quantity first. Rating, sold count, original price, and shop name appear only when the data is available on the product; out-of-stock products show a disabled button.

## Gaps and acceptance direction

`DashboardController` does not yet pass `$announcements`, so the banner shows the welcome fallback. When it does, it should include only published announcements whose audience covers Buyers (All Users, Buyers & Sellers, Buyers Only). Scheduled announcements are not published automatically when `scheduled_at` passes; either include due scheduled rows or add a scheduler.

The controller takes 12 products; the grid is laid out for 18. Seller shop name loads lazily; eager-load `seller.sellerDetail`. Rating, sold count, and original price need an average-rating aggregate, a sold-quantity aggregate from completed orders, and a new original-price column; none exist yet. Filtering by a parent category matches only products assigned directly to it, so it can miss products assigned to subcategories.

Add to Cart without a page reload depends on the Alpine AJAX plugin and has not been browser-tested. Owner verification of the header, menu, banner, quick-select sheet, and responsive layouts is pending.

## Source evidence

`resources/views/components/buyer/layout.blade.php`, `resources/views/buyer/dashboard.blade.php`, `resources/views/buyer/partials/product-card.blade.php`, `resources/views/buyer/partials/quick-add.blade.php`, `resources/css/buyer/layout.css`, `app/Http/Controllers/Buyer/DashboardController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the [Buyer domain page](../../../domains/Buyer.md), [Browse Shop](../browse-shop/spec.md), [Cart](../view-cart/spec.md), and [feature implementation guide](../../../feature-implementation-guide.md).