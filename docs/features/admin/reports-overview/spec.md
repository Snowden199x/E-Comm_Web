# Reports Overview

**Status:** Implemented for daily/monthly sales, commission and delivery-time preview/PDF; definitions still need owner review
**Reviewed:** 11 November 2026

## What the page shows

`GET /admin/reports` with `view=daily|monthly`, a date filter and custom date. The same data feeds the in-page preview (`admin.reports.preview`), the main body partial and the PDF download.

- **Sales summary:** gross sales (orders placed, not cancelled), total orders, average order value, completed orders (delivered/completed) and returns/refunds (orders in `returned`).
- **Commission:** per order from its saved Seller rate, never the current setting.
- **Seller breakdown and trend chart:** completed item sales and commission per day/month.
- **Delivery time (11 November 2026):** among delivered/completed orders in the period with a `delivered_at`, the average time from the order being placed to the delivered scan, in days, next to the same-day and over-3-day counts. It is a **measurement, not an SLA target**: no promise, penalty or on-time percentage is defined yet. Orders without a `delivered_at` are excluded and the count of measured parcels is shown, so the average is not read as covering every order. Shown on the page, the preview and the PDF.

## Rules and limits

- Dates are order `created_at`; the delivery time uses each order's own `created_at` → `delivered_at`, so a period can include parcels delivered later.
- Commission is merchandise-only; shipping is not part of it. See [commission and settlement](../../shared/commission-settlement/spec.md).
- No CSV/Excel export for Admin (PDF only). Logistics has its own scoped reports with PDF/CSV.
- Definitions (gross vs completed, what counts as a return) are still pending owner confirmation before they are treated as financial statements.

## Source evidence

`app/Http/Controllers/Admin/ReportController.php`, `resources/views/admin/reports/`, `resources/views/admin/reports/pdf.blade.php`.

## Related documentation

See [Admin delivery fees and remittances](../delivery-fees-and-remittances/spec.md), [commission settings](../commission-settlement/spec.md), and [domain status](../../../domain-feature-status.md).
