# Seller Earnings

**Status:** Web page written in code; owner verification pending. Earnings credit on delivery (11 November 2026), so the cash-chain design in [COD cash and delivery fee distribution](../../../design/cod-cash-and-delivery-fee-distribution.md) no longer gates this page. Payout execution does not exist and is not planned while the money flow is display-only.

**Earnings** (`GET seller/earnings`, `seller.earnings.index`, sidebar item after Delivered Orders) lists the signed-in Seller's delivered or completed orders (`Order::SALES_STATUSES`) with delivery date, item price, any voucher, Vendo's commission and the Seller's earnings. There is no bank or e-wallet payout and no real money moves: earnings are only displayed.

**Calculation** (`SellerEarningsCalculator`, integer centavos, half-up): commission is the order's saved `seller_commission_rate` (the fixed 10% for orders placed before rates were saved) applied to the gross item price (`orders.total_amount`); earnings are items minus the order's voucher discount minus commission, never below zero. Seller vouchers are Seller-funded, so the discount comes off the Seller's earnings, because the Buyer, and therefore Vendo, collected that much less cash. Example: ₱500 items, 10%, no voucher gives ₱50 commission and ₱450 earnings; with a ₱50 voucher, ₱400.

**Status (11 November 2026).** Earnings are **credited as soon as the order is `delivered`** and stay credited after the Buyer confirms receipt (`completed`); the page no longer waits for the cash chain. `orders.money_status` is still maintained for the COD cash pages but does not gate earnings. Because every listed order is credited, the page has no Pending/Earlier tabs or status filter: it shows two cards (Credited total, commission on credited orders), one `Credited` badge per row, and pagination (15 per page). The page is scoped to the signed-in Seller's own orders and lists only `Order::SALES_STATUSES`.

When a delivered scan creates a shipping settlement (a facility-routed order with a delivery fee), `ShippingEarningsSettlementService` sends the Seller one `earnings_credited` notification with that order's net amount, computed with the same `SellerEarningsCalculator` (items minus voucher minus the commission saved on the order). The settlement is idempotent, so a retried scan does not send a second notice, and confirming the cash later no longer sends one.

Not built: payout details or withdrawals (not needed) and CSV export.
