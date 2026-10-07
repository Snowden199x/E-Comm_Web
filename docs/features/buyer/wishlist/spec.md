# Wishlist

**Status:** Buyer-scoped saved-items persistence implemented; owner verification and migration pending
**Reviewed:** 7 October 2026

## Current behavior

Buyer product cards and the header provide a heart action, saved-item count, slide-over panel, Dashboard "Saved for later" row, and Saved items section in Account/Settings. `buyer_saved_items` stores a unique Buyer/product pair. The page loads the signed-in Buyer's saved products from the server; add, remove, clear, and list endpoints derive ownership from the session and reject non-Buyers. Only approved products from approved active Sellers can be added or returned. The list is capped at 60. Existing browser-local IDs are imported once after the migration, with visibility checks, then removed from local storage. Saving an item does not change the cart.

## Gaps and acceptance direction

Unavailable, removed, or unapproved products are omitted from the displayed list. Stale references are pruned on the next save/import, or removed by the product foreign key when a product is deleted. A future UI may show an explicit unavailable state. The browser import and cross-device sync need owner verification.

## Source evidence

`app/Http/Controllers/Buyer/PersonalizationController.php`, `app/Services/BuyerPersonalizationService.php`, `database/migrations/2026_10_07_100000_create_buyer_saved_items_and_settings.php`, `resources/views/buyer/partials/favorites-panel.blade.php`, `resources/views/components/buyer/layout.blade.php`, `routes/web.php`

## Related documentation

See the [7 October frontend pull review](../../../design/2026-10-07-pulled-frontend-review.md), [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
