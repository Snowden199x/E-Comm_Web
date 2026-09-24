# Seller Inventory

**Status:** Partial: display only  
**Reviewed:** 24 September 2026

## Current behavior

Seller dashboard shows low and out-of-stock product alerts from seller-owned products.

## Gaps and acceptance direction

No seller product CRUD, stock adjustment, stock ledger, or restock action route is present.

## Source evidence

`app/Http/Controllers/Seller/DashboardController.php`, `app/Models/Ecommerce/Product.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
