# Seller Category Requests

**Status:** Implemented in code; migration and owner verification pending.
**Reviewed:** 12 October 2026

Approved active Sellers can request additional top-level selling categories from Account Management. The request includes one or more unapproved categories, a required JPG/PNG/PDF permit of at most 5 MB, and an optional note. The server rejects duplicate categories already approved or pending, stores the permit on the private local disk, and notifies Admin. Seller Account Management shows pending and reviewed requests.

Admin can review requests at **Seller Category Requests**, open the protected permit, and approve or reject once. Rejection requires a note. Approval attaches the categories to the Seller's approved category pivot; the decision, reviewer, and time are saved, an Admin action is logged, and the Seller receives a notification. Admin can access permits only through the guarded route; they are never placed in public storage.

This is category access only. Product review and category-specific licensing decisions remain Admin responsibilities. The owner runs the migration and verifies the forms and document viewer.

**Source:** `app/Http/Controllers/Seller/CategoryRequestController.php`, `app/Http/Controllers/Admin/SellerCategoryRequestController.php`, `app/Models/Profiles/SellerCategoryRequest.php`, and `database/migrations/2026_10_12_110000_create_seller_category_requests.php`.
