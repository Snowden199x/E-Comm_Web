# Assign Courier to Order

**Status:** Partial
**Reviewed:** 26 September 2026

An approved active origin logistics center sees its routed ready orders and can assign an approved active pickup rider linked to that center. Seller cannot mark pickup; the assigned rider's QR/barcode scan through the rider API makes that transition. After manual sorting and destination receipt, the destination center can assign its own approved active delivery rider. Same-center orders can assign delivery directly from `sorted`. Assignment actions lock the order, reject duplicate/wrong-stage requests, check center ownership and rider membership, and write an assignment audit row. Delivery assignment also records a status event.

Routing uses a unique city match or, failing that, a unique province match among approved active centers. It does not rank by physical distance or capacity. Ambiguous/missing matches stay unresolved. The versioned API exposes assigned work and three scan transitions for the later mobile client. Courier acceptance, reassignment, mobile client UI, proof of delivery, and delivery completion are not implemented.

Source: `app/Services/OrderRoutingService.php`, `app/Http/Controllers/Logistics/DispatchController.php`, `resources/views/logistics/dispatch/index.blade.php`.
