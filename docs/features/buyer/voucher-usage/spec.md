# Voucher Usage

**Status:** Checkout selection and first-activation notifications implemented in code; owner migration and manual verification pending
**Reviewed:** 13 October 2026

## Current checkout behavior

Buyers can select a matching Seller voucher or enter one code per shop at checkout and request a quote for selected cart lines. The server enforces Seller, product/category scope, schedule, minimum spend, total uses, and per-Buyer uses. The final checkout rechecks under a row lock, snapshots the code and discount on each Seller order, and reduces the Buyer payable amount. Cancellation before pickup releases the redemption once. See [Seller Vouchers](../../seller/vouchers/spec.md).

## Behavior and remaining gaps

At checkout, each Seller order shows active, unexpired vouchers matching the Seller's selected lines: shop-wide, selected products, or selected main categories. The selector and offer details show code, discount, eligible products/categories, minimum spend, start/end time, and use limits. Offers that cannot yet be redeemed because of a future start, minimum spend, Buyer limit, or exhausted total-use limit remain visible with the reason and cannot be selected. Selecting an eligible offer applies a server-calculated quote; Buyers may still enter an unlisted code. The Buyer may use one voucher per Seller order. The final order transaction remains authoritative.

When a Seller first activates a voucher, notify Buyers who have at least one prior Seller order. The notification identifies the shop, voucher code, and scope, and opens that Seller's shop. A unique Buyer/voucher record prevents duplicate notifications. Pausing, editing, or reactivating a previously announced voucher does not send another alert. The notification query and destination remain Buyer-scoped and expose no other Buyer's order details. Scheduled vouchers are announced when first activated; the checkout selector shows them once their start time arrives.

Stacking, free-shipping vouchers, post-delivery refunds, and settlement accounting are future work. Shipping quotations remain separate and pending.

### Acceptance criteria for discovery and alerts

- At checkout, each Seller order shows active, unexpired vouchers matching the products/categories in that Seller's selected cart lines, along with the configured code and terms.
- The Buyer can distinguish eligible products/categories, minimum spend, validity window, and usage limits before selecting a voucher. Offers that currently fail a rule remain visible with the reason and cannot be selected.
- The Buyer can select at most one voucher for each Seller order; the server revalidates its scope and usage before order creation.
- A voucher's first activation creates one notification for each active, non-archived Buyer with a prior order from that Seller; unrelated Buyers and other Sellers' customers are excluded.
- Pausing, editing, or reactivating the same voucher does not create duplicate “new voucher” notifications.
- A notification opens the Seller's shop and contains no order details belonging to another user; the voucher is selectable at checkout when matching products are selected.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Services/SellerVoucherService.php`, `app/Services/SellerVoucherNotificationService.php`, `app/Models/Ecommerce/SellerVoucher.php`, and the 13 October notification-recipient migration.

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
