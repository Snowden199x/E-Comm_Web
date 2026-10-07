# Assign Courier to Order

**Status:** Partial
**Reviewed:** 28 September 2026

An approved active origin logistics center sees its routed ready orders and can assign an approved active pickup rider linked to that center. Seller cannot mark pickup; the assigned rider's QR/barcode scan through the rider API makes that transition. After manual sorting and destination receipt, the destination center can assign its own approved active delivery rider. Same-center orders can assign delivery directly from `sorted`. Assignment actions lock the order, reject duplicate/wrong-stage requests, check center ownership and rider membership, and write an assignment audit row. Delivery assignment also records a status event.

Routing prefers an exact municipality match among approved active Main Hubs; without one, it selects the unique nearest center in the same province using approximate municipality coordinates. It does not rank by capacity or road/ferry distance. Ambiguous/missing matches stay unresolved. The mobile API exposes assigned Rider work and seven current scan transitions. Logistics manually assigns and can reassign an approved Rider; the Rider has no accept/decline action. The origin pickup Rider can currently advance legacy SOC-coded inter-hub stages after sorting. Configured SH5/SH6/SH3 locality routes can be selected at origin sorting, but actual locality scans, truck departure/arrival, manifests, and local Rider pickup from an SH are not implemented. Rider and Truck interfaces are mobile-only; the web app owns protected Logistics Center tools and backend APIs. Independent proof of delivery and exception actions remain missing.

Source: `app/Services/OrderRoutingService.php`, `app/Http/Controllers/Logistics/DispatchController.php`, `resources/views/logistics/dispatch/index.blade.php`.
