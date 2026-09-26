# Shipments — Figma Design Functions

**Reference:** Two seller Shipments screenshots supplied by the user: shipment list and open Shipment Details drawer.  
**Status:** Original design reference. The screens are now backed by ERP orders; see the [implementation spec](spec.md) for final rules. The descriptions below preserve the supplied mockup and its original ambiguities.  
**Reviewed:** 24 September 2026

## Purpose and entry point

Seller opens **Order Management → Shipments** to view shipment progress, search/filter records, and inspect one shipment. Sample names, addresses, tracking numbers, dates, quantities, and money amounts in the screenshots are placeholders.

## Shipment list screen

| Element | Visible function |
|---|---|
| Summary cards | Display Total Shipments, To Ship, In Transit, and Delivered counts. |
| Search | Search by order ID, customer, or tracking number, as stated in the input placeholder. |
| Status filter | Narrow the list by shipment status; exact dropdown options are not shown. |
| Date range filter | Filter shipment records over a selected period; which date field is filtered is not specified. |
| Courier filter | Narrow the list by courier; the screenshot shows an All Courier default but no option list. |
| Shipment table | Show order ID, customer name/location, item thumbnail/count, date/time, courier, tracking number, status, and actions. |
| View | Open Shipment Details for the selected row, as illustrated by the second screenshot. |
| Row overflow menu | Provide additional actions; menu contents are not visible and remain unspecified. |
| Pagination | Show result range/total, page numbers, and previous/next controls. |
| Shipment Overview | Show a donut chart with counts/percentages for To Ship, In Transit, Delivered, and Canceled, plus View all. |
| Recent Shipments | Show recent order IDs, customer names, status badges, and times, plus View all. |

The destinations of both **View all** links and whether summary cards/chart segments are clickable are not defined by these images.

## Shipment Details drawer

The selected table row is highlighted and a panel opens on the right while the shipment list remains visible.

| Section | Information/function |
|---|---|
| Header | Shipment Details title, current status badge, and order identifier. |
| Order Information | Order ID, order date/time, and current status. |
| Customer Information | Customer name, contact number, and delivery address. |
| Tracking Information | Courier, tracking number, and estimated delivery date range. |
| Ordered Items | Product thumbnail/name, selected variation, quantity, unit price, and line subtotal. |
| Totals | Total item quantity, shipping fee, and total amount. |
| Mark as Shipped | Visible action intended to advance shipping progress; exact transition and eligibility are not defined. |
| Cancel Shipment | Visible cancellation action; allowed states, reason form, confirmation, and effects are not defined. |

The drawer shows a To ship example. Available actions for in-transit, delivered, or canceled records are not shown. A close control, outside-click dismissal, and keyboard behavior are not shown either; document them when supplied or agreed.

## Fit with the existing ERP flow

These were the design questions. Their implemented resolution is in the [implementation spec](spec.md); the following records the original comparison:

- **To Ship / In Transit:** These display labels must be mapped to the existing ERP states. Do not add a `shipped` state just because the button uses that label.
- **Mark as Shipped:** Seller actions stop at `ready_for_pickup`. The assigned pickup rider's future QR/barcode scan will move the order to `picked_up`; a seller shipping button must not bypass custody confirmation.
- **Cancel Shipment:** Existing seller decline only cancels a `placed` order and restores stock. That behavior does not establish a policy for canceling a shipment later in fulfillment. Determine whether this action cancels an order, a dispatch booking, or a shipment; then define stock/payment effects and notifications.
- **Courier and tracking:** The screenshots name a carrier company. Current `orders.courier_id` refers to a courier user. Carrier company, individual rider, tracking number, and ETA are different data concepts; their source/relationship remains to be defined. A sample carrier name does not mean an external carrier integration exists.
- **Shipping fee:** The drawer displays a shipping charge. Current checkout totals are item totals; shipping quotation and shipment charge persistence are not yet implemented.

## Design notes to resolve

- The first screenshot is titled Shipments but uses a products/inventory subtitle. The second has Products & Inventory as the background page title even though Shipments is selected. Use a consistent Shipments title and shipment-related subtitle when the design is finalized.
- Card values, chart totals/percentages, repeated rows, and pagination counts are mock data and are not mutually authoritative. Real counts should use agreed shipment statuses and reporting scope.
- Define whether overview/cards/recent shipments follow the active table filters or summarize all seller shipments.
- The list date, order date in the drawer, ETA, and recent-activity time need distinct labels/data sources if they represent different events.

## Current implementation and related docs

Seller Shipments routes serve live lists, details, persisted tracking metadata and pre-pickup cancellation. Orders and Shipments share the same transition service. Logistics dispatch now assigns pickup and delivery riders and records manual hub handoffs; rider scans and delivery execution remain future work. See the [implementation spec](spec.md).

- [Seller domain](../../../domains/Seller.md)
- [Seller order management](../order-management/spec.md)
- [Shipment fulfillment](../../shared/shipment-fulfillment/spec.md)
- [ERP order and logistics flow](../../../order-logistics-flow-decisions.md)

Source: `routes/web.php`, `app/Http/Controllers/Seller/OrderController.php`, `app/Models/Ecommerce/Order.php`, and `app/Http/Controllers/Buyer/CheckoutController.php`.
