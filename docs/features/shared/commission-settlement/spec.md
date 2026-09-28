# Commission and Settlement

**Status:** Partial  
**Reviewed:** 28 September 2026

## Current behavior

The owner confirmed that Vendo's platform commission is 10%. `CommissionSetting::currentRate()` and the commission-settings migration default to 10.00. The Admin commission screen can edit the stored rate, so a database override may differ from the default and must be checked before publishing a fixed-rate policy. Current Admin reports calculate commission from order-item price × quantity for orders in `delivered` or `completed` status. The Seller report displays sales, units, shipping, and outcomes; it does not display the current commission rate or a commission balance. Category commission fields exist in the schema but the current Admin commission calculation uses the global rate.

## Gaps and acceptance direction

No settlement ledger, payout execution, reversal, or reconciliation workflow found. The current configured database rate was not verified in this documentation pass.

## Source evidence

`app/Models/Finance/CommissionSetting.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
