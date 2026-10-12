# Seller Vouchers

**Status:** Implemented in code; migration and owner verification pending.
**Reviewed:** 12 October 2026

An approved active Seller can create, list, filter, edit, pause, resume, and retire shop voucher codes. Codes are unique per Seller, 4–12 uppercase letters or digits. A voucher can be a fixed amount or at most 90% off, optionally capped, with a minimum eligible spend, overall/per-Buyer usage limits, and start/end times. Scope is all Seller products, selected Seller products, or approved Seller main categories (including their subcategories). Server validation checks ownership and the limits. Retiring a code keeps its redemption history and reserves its code.

Buyer checkout accepts one code per Seller order. A Buyer applies the code to see a server-calculated discount before placing the order. The checkout transaction locks the voucher and selected cart rows, rechecks eligibility and usage, saves `voucher_code` and `voucher_discount` on the order, and records one redemption. `orders.total_amount` remains the gross product subtotal; payable COD is subtotal minus voucher discount plus shipping. A permitted Buyer/Seller pre-pickup cancellation voids the redemption and releases its usage count once. Past orders retain their code and discount snapshot even if the Seller edits or retires the voucher.

The Seller voucher page shows nonvoided redemption counts and discount totals. Buyer order details, Seller order/shipment/completed-order details, and the waybill show the discounted payable amount. The Admin product commission remains 10% of item price × quantity in delivered/completed orders; voucher funding and net Seller settlement still need an explicit business rule.
The Seller product list counts currently available vouchers that apply to each loaded product, including shop-wide, product-specific, and main-category codes.

There is no voucher discovery/catalog on Buyer cart or product pages, stacking, free-shipping vouchers, refund ledger, or payout accounting. Only manually entered shop codes are supported. The preview quote is revalidated at final checkout and can change if stock, voucher status, or usage changes before submission. Shipping remains zero pending a tariff decision. The owner must run migrations and verify the flow.

**Source:** `app/Http/Controllers/Seller/VoucherController.php`, `app/Services/SellerVoucherService.php`, `app/Http/Controllers/Buyer/CheckoutController.php`, `resources/views/seller/vouchers/index.blade.php`, `resources/views/buyer/checkout.blade.php`, and `database/migrations/2026_10_12_120000_create_seller_vouchers.php`.
