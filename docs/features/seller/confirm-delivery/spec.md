# Confirm Pickup Handover

**Status:** Rider pickup scan backend implemented; rider client pending
**Reviewed:** 26 September 2026

Seller can prepare an order through `ready_for_pickup` and can cancel before physical pickup. Seller cannot move it to `picked_up`. The origin logistics center assigns an approved rider; the assigned rider's QR/barcode scan through the versioned API verifies the rider/center assignment and moves the order to `picked_up`. The future rider client still needs to perform the camera scan. Buyer confirms receipt only after delivery, whose completion action is not yet implemented.

Source: `app/Services/SellerOrderWorkflow.php`, `app/Http/Controllers/Seller/OrderController.php`, `app/Http/Controllers/Seller/ShipmentController.php`.
