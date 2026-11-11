# Product Search

**Status:** Implemented (server-side `LIKE` search with relevance ordering and filters; no search index)
**Reviewed:** 11 November 2026

## Current behavior

The Buyer header search box on every Buyer page submits `search` to `buyer.products.index` and keeps the entered text on the results page. `search` matches the product **name, brand or description** (case-insensitive `LIKE`), capped at 80 characters.

The listing page now has a filter bar that stays in the URL, so a shared or reloaded link keeps the same results:

- **Search** — refine the text without returning to the header box.
- **Brand** — one brand from the brands currently listed by approved Sellers (up to 60 options).
- **Lowest price / Highest price** — inclusive range on `products.price`; a reversed pair is swapped rather than refused.
- **In stock only** — `stock > 0`.
- **Sort by** — **Best match** (default while searching), **Newest** (default when browsing), **Price: low to high**, **Price: high to low**.
- **Apply filters / Reset**, plus Clear filters in the page header. The category, when opened from a category card, is kept as a hidden filter.

**Relevance** is computed in SQL, not by a search service: an exact name match ranks first, then a name that starts with the text, then a name that contains it, then a description/brand-only match; ties fall back to newest. A search with no match shows an empty state with Browse all products and, when shop search exists, Search shops instead.

**Shop search:** the header Products/Shops selector can submit to `buyer.sellers.index`. The server searches approved, active, non-archived Sellers by shop business name or account name, and paginates results. See [Seller shop](../seller-shop/spec.md).

## Gaps

- No dedicated search index or full-text ranking; `LIKE` scans the catalog, which is fine at the current listing size and will need an index (or Scout) as the catalog grows.
- Filters do not include rating, condition, location or seller, and there is no saved-search or recent-search memory.
- The Buyer mobile app catalog does not send these filters yet.

## Source evidence

`app/Http/Controllers/Buyer/ProductController.php`, `resources/views/buyer/products/index.blade.php`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
