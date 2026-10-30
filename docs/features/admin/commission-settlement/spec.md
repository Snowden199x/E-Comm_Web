# Commission Settings

**Status:** Partial: per-order seller-rate commission reports and editable rates exist; no settlement ledger
**Reviewed:** 29 October 2026

## Current behavior

Admin can inspect Seller/month merchandise totals and calculated commission. Calculations include item price × quantity for orders in `delivered` or `completed` status and apply each order's saved seller commission rate to that gross item value. The rate is editable on the Admin Delivery Fees page (`delivery_fee_settings.seller_commission_rate`, default 10) and the old Commission POST endpoint is still gone. Seller vouchers reduce the Buyer's payable total, but currently do not reduce the Admin commission base. The displayed amount is a report calculation, not a payable balance, and is separate from the Logistics/Rider shipping earnings.

## Delivery fee and seller commission rates (28 October 2026)

The seller commission is now an editable setting (`delivery_fee_settings.seller_commission_rate`, default 10) on the Admin Delivery Fees page, and each new order saves the rate in force. This page and the Reports now calculate commission per order from the saved rate (`COALESCE(orders.seller_commission_rate, 10)`), so a rate change never alters past commissions; `CommissionSetting::currentRate()` now returns the current setting for display. See the [Admin delivery fees and cash remittances spec](../delivery-fees-and-remittances/spec.md).

## Gaps and acceptance direction

Each order now carries the seller rate it was placed with, so past commissions do not move when the rate changes; there is still no commission ledger row per order and no invoice. Route-leg tariffs, Logistics/Rider beneficiary allocation, payout/settlement ledger, reconciliation, reversals, and disbursement workflow are not implemented. The policy for funding Seller vouchers and settling the Seller's net proceeds also needs a decision. See the [commission calculation and settlement design](../../../design/commission-calculation-and-settlement.md) for the Aisley-based shipping reference and Vendo-specific boundaries.

## Source evidence

`app/Http/Controllers/Admin/CommissionController.php`, `app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
