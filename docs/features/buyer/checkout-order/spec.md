# Checkout and Order Creation

**Status:** Implemented core COD-style order creation  
**Reviewed:** 6 October 2026

## Current behavior

Checkout requires selected cart item IDs from the buyer's cart. The server validates their ownership and a checkout revision, validates street/barangay detail, a province/city choice from the bundled location catalog, and COD payment mode, locks the selected rows/products, checks stock and current product data, groups selected items by seller, creates order and item snapshots, decrements stock, and notifies each seller inside a transaction. Only purchased cart rows are removed; unselected rows remain. Buyer purchases do not create admin `new_order` notifications.
**Page layout (6 October 2026):** the page starts with a **Delivery address** section. The Buyer's registered address is the first choice, labelled Default and selected automatically; if it is incomplete it is disabled with a link to Account Management. **Add new address** opens a dialog (label, house number/street/barangay/ZIP, province, city) and the new address is selected for this order. Below are the items grouped by shop, the Cash on Delivery payment method, and a sticky order summary with Place order, which stays disabled until a usable address is selected. The form still posts the same fields: `items[]`, `checkout_revision`, `payment_mode`, `shipping_address`, `shipping_province_code`, and `shipping_city_code`, so server validation is unchanged. Extra addresses added in the dialog are kept only in the browser tab for up to two hours; see [Buyer Address](../address-book/spec.md).

After a failed submit the address the Buyer had chosen is kept as "Address you entered", and an accidental same-tab reload restores addresses added in the dialog for up to two hours. The current server-generated cart selection and checkout revision remain authoritative. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Payment gateway, shipping quotation, taxes/fees, idempotency key, and checkout retry semantics are not present in this scope.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Ecommerce/Order.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).