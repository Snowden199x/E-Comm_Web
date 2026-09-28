# Confirm Pickup Handover

**Status:** Rider pickup scan backend and separate rider client implemented; owner verification pending
**Reviewed:** 26 September 2026

Seller can prepare an order through `ready_for_pickup` and can cancel before physical pickup. Seller cannot move it to `picked_up`. The origin logistics center assigns an approved rider; that rider's QR/barcode scan through the separate mobile client and versioned API verifies the assignment and moves the order to `picked_up`. Current cross-hub parcels still use transitional SOC-coded states after origin sorting; physical SH route scans are future work. The assigned destination delivery rider can scan the parcel as delivered; the buyer separately confirms receipt before rating products. Independent delivery proof is not yet implemented. Courier, rider, and truck interfaces are mobile-only.

Source: `app/Services/SellerOrderWorkflow.php`, `app/Http/Controllers/Seller/OrderController.php`, `app/Http/Controllers/Seller/ShipmentController.php`.
