# Complete Delivery

**Status:** Assigned rider delivery scan implemented; independent proof pending
**Updated:** 27 September 2026

## Current behavior

The approved delivery rider assigned by the destination logistics hub can scan the matching shipping-label QR or barcode after handing over the parcel. `POST /api/v1/rider/scans` with `scan_type=delivered` moves only `out_for_delivery → delivered`. It checks the rider's approved center membership and order assignment, locks the order, writes an idempotent scan event and a status event, and updates seller, buyer and center notifications. The order's existing `delivered_at` timestamp is set by the status transition. The scan is a rider report, not buyer confirmation.

## Gaps and acceptance direction

The buyer separately confirms receipt (`delivered → completed`) in the web order page; only then can they review purchased products. A signature, photo, OTP, GPS evidence, failed-attempt path and dispute policy remain future work. A scan alone cannot independently prove physical handover.

## Source evidence

`app/Services/OrderScanWorkflow.php`, `app/Http/Controllers/Api/RiderScanController.php`, `app/Models/Ecommerce/Order.php`, and the separate `vendo_rider` live work screen.

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
