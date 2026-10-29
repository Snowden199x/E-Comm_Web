# Seller Earnings

**Status:** Web page written in code; owner verification pending. Part of the [COD cash and delivery fee design](../../../design/cod-cash-and-delivery-fee-distribution.md).

**Earnings** (`GET seller/earnings`, `seller.earnings.index`, sidebar item after Delivered Orders) lists the signed-in Seller's delivered or completed orders (`Order::SALES_STATUSES`) with delivery date, item price, any voucher, Vendo's commission and the Seller's earnings. There is no bank or e-wallet payout and no real money moves: earnings are only displayed.

**Calculation** (`SellerEarningsCalculator`, integer centavos, half-up): commission is the order's saved `seller_commission_rate` (the fixed 10% for orders placed before rates were saved) applied to the gross item price (`orders.total_amount`); earnings are items minus the order's voucher discount minus commission, never below zero. Seller vouchers are Seller-funded, so the discount comes off the Seller's earnings, because the Buyer, and therefore Vendo, collected that much less cash. Example: ₱500 items, 10%, no voucher gives ₱50 commission and ₱450 earnings; with a ₱50 voucher, ₱400.

**Status.** **Pending**: the order has cash tracking but Vendo has not yet confirmed its cash; the page shows how far the cash has travelled (for example "Remitted to Province Hub"). **Credited**: `money_status` is `credited_to_seller`. When an Admin confirms a Logistics Company's batch to Vendo, its orders move to `credited_to_seller` automatically; a discrepancy keeps them Pending until resolved. **Earlier order**: `money_status` is null (placed before cash tracking); these are listed but excluded from totals. The top cards show Pending, Credited and commission on credited orders; a status filter and pagination (15 per page) are available. The page is scoped to the signed-in Seller's own orders.

Not built: payout details or withdrawals (not needed), a Seller notification when earnings are credited, and CSV export.
