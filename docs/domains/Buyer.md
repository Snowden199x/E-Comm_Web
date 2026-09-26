# Buyer Domain

## Purpose

Buyers register, discover products, maintain a cart, place seller-specific orders, track status, manage a profile, and message other parties.

## Implemented journey

Registration collects profile/address/ID data and requires an email OTP proof stored in the current session and a verified database record. The cart groups items by store and lets buyers select individual items or whole stores for checkout. Checkout validates selected cart rows and stock under row locks, groups selected items by seller into separate orders, decrements stock transactionally, removes only purchased cart rows, and creates an initial order event. Buyers can list/view their orders and confirm receipt after delivery. Product/category pages, account editing, cart operations, and messaging routes are present.

## Boundaries and gaps

Checkout accepts a payment mode but this code snapshot does not demonstrate a payment-gateway integration. Buyer cancellation/refund, review submission, saved wishlist, voucher application, recently viewed products, and buyer support tickets are not evidenced as working routes. Address defaults are built from profile fields; the current registration stores the combined street and leaves `house_no` null.

See `features/buyer/` and [order flow](../order-logistics-flow-decisions.md).
