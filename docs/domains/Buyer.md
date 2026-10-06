# Buyer Domain

## Purpose

Buyers register, discover products, maintain a cart, place seller-specific orders, track status, manage a profile, and message other parties.

## Implemented journey

Registration collects profile/address/ID data and requires an email OTP proof stored in the current session and a verified database record. The cart groups items by store and lets buyers select individual items or whole stores for checkout. Checkout validates selected cart rows and stock under row locks, groups selected items by seller into separate orders, decrements stock transactionally, removes only purchased cart rows, and creates an initial order event. Buyers can list/view their orders, confirm receipt after the assigned rider scans delivery, then use the final Rate Product progress action to review purchased items. Product/category pages, account editing, cart operations, and messaging routes are present. Buyer pages share a purple storefront header with search, Messages, Cart, Notifications, an account menu, and a category/subcategory menu; the Dashboard shows an announcement banner, categories, and recommended products with Add to Cart (see `features/buyer/dashboard/`).

## Boundaries and gaps

Checkout accepts a payment mode but this code snapshot does not demonstrate a payment-gateway integration. Buyer cancellation/refund, saved wishlist, voucher application, recently viewed products, and buyer support tickets are not evidenced as working routes. Address defaults are built from profile fields; the current registration stores the combined street and leaves `house_no` null.

The 6 October refresh restyled the Product detail, Product list, Checkout, My Orders, Order detail, Shop, and Notifications pages and added a shared empty-state component. See [Product detail](../features/buyer/product-detail/spec.md), [Seller shop](../features/buyer/seller-shop/spec.md), [Buyer notifications](../features/buyer/notifications/spec.md), and [backend needs](../backend-needs.md) for what the server still has to add (Buyer cancel rule, shop search, saved addresses).

See `features/buyer/` and [order flow](../order-logistics-flow-decisions.md).