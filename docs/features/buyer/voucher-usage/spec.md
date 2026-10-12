# Voucher Usage

**Status:** Shop-code checkout redemption implemented in code; migration and owner verification pending
**Reviewed:** 12 October 2026

## Current behavior

Buyers can enter one Seller voucher code per shop at checkout and request a quote for their selected cart lines. The server enforces Seller, product/category scope, schedule, minimum spend, total uses, and per-Buyer uses. The final checkout rechecks under a row lock, snapshots the code and discount on each Seller order, and reduces the Buyer payable amount. Cancellation before pickup releases the redemption once. See [Seller Vouchers](../../seller/vouchers/spec.md).

## Gaps and acceptance direction

Voucher discovery on the cart/product pages, stacking, free-shipping vouchers, post-delivery refunds, and settlement accounting are not implemented. Shipping quotations remain separate and pending.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Services/SellerVoucherService.php`, `app/Models/Ecommerce/SellerVoucher.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
