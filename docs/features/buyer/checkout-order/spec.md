# Checkout and Order Creation

**Status:** Implemented core COD-style order creation  
**Reviewed:** 26 September 2026

## Current behavior

Checkout requires selected cart item IDs from the buyer's cart. The server validates their ownership and a checkout revision, validates street/barangay detail, a province/city choice from the bundled location catalog, and COD payment mode, locks the selected rows/products, checks stock and current product data, groups selected items by seller, creates order and item snapshots, decrements stock, and notifies each seller inside a transaction. Only purchased cart rows are removed; unselected rows remain. Buyer purchases do not create admin `new_order` notifications.

## Gaps and acceptance direction

Payment gateway, shipping quotation, taxes/fees, idempotency key, and checkout retry semantics are not present in this scope.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Ecommerce/Order.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
