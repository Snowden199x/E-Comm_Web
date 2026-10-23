# Rider Delivery-Proof API

**Status:** Web endpoint and Rider mobile upload flow implemented in code; migration and owner/device verification pending.

`POST /api/v1/rider/delivery-proofs` uses the existing approved Rider Sanctum token. The order must be assigned to the authenticated Rider for final delivery at that Rider's approved Logistics Center, be `out_for_delivery`, and have been received and sorted at the Rider's destination Station Hub. Orders with an open Logistics exception cannot be completed.

Required JSON/multipart fields: `tracking_number`, stable UUID `scan_key`, `recipient_name` (2–120 chars), and `photo` (JPG/JPEG/PNG/WebP, maximum 5 MB). `notes` is optional, maximum 500 chars. The photo is saved on the private local disk under `private/delivery-proofs/{order_id}`. In one transaction, the API creates a single proof row and calls the existing scan workflow to record `out_for_delivery → delivered`. The same Rider and UUID can retry idempotently; a different key after proof exists is rejected. The Buyer still confirms receipt separately to move `delivered → completed`.

The Rider app requests recipient name and a camera photo when the assignment response marks proof as required, then submits this endpoint with the same persisted UUID used for scan retries. The order proof route returns the private image only to the order's Buyer, Seller, or Logistics staff whose Company/facility is on that order's route. The web order views link to the proof once recorded. The proof captures recipient-entered name and a photo; it does not verify GPS, identity, signature, or buyer consent.

Migration `2026_10_23_200000_create_order_delivery_proofs.php` creates `order_delivery_proofs`. Production must permit the configured request body size and persist the Laravel private local disk; do not expose this folder through `public/storage`.
