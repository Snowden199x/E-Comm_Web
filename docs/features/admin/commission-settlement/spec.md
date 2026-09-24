# Commission Settings

**Status:** Partial: rate editing/reporting exists  
**Reviewed:** 24 September 2026

## Current behavior

Admin can inspect commission settings and seller details and update rates; reports aggregate seller commission-related data.

## Gaps and acceptance direction

A payout/settlement ledger, reconciliation, tax rules, and disbursement workflow are not evidenced.

## Source evidence

`app/Http/Controllers/Admin/CommissionController.php`, `app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
