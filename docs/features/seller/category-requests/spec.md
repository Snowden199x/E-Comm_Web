# Seller Category Requests

**Status:** Implemented in code; migration and owner verification pending (Admin page restyled 31 October 2026).
**Reviewed:** 31 October 2026

Approved active Sellers can request additional top-level selling categories from Account Management. The request includes one or more unapproved categories, a required JPG/PNG/PDF permit of at most 5 MB, and an optional note. The server rejects duplicate categories already approved or pending, stores the permit on the private local disk, and notifies Admin. Seller Account Management shows pending and reviewed requests.

Admin reviews requests at **Category requests** (Pending, Approved and Rejected tabs with counts; 12 rows per page). The page is a list of requests; opening one shows a dialog with the seller, the requested categories, the seller's note and the decision. **View permit** opens the protected permit in the shared Admin document popup (never a new tab). Admin approves or rejects once. Rejection requires a note: the browser blocks an empty note and the server validates it, with the message shown under the note field. Approval attaches the categories to the Seller's approved category pivot; the decision, reviewer, and time are saved, an Admin action is logged, and the Seller receives a notification. Admin can access permits only through the guarded route; they are never placed in public storage.

This is category access only. Product review and category-specific licensing decisions remain Admin responsibilities. The owner runs the migration and verifies the forms and document viewer.

**Source:** `app/Http/Controllers/Seller/CategoryRequestController.php`, `app/Http/Controllers/Admin/SellerCategoryRequestController.php`, `app/Models/Profiles/SellerCategoryRequest.php`, and `database/migrations/2026_10_12_110000_create_seller_category_requests.php`.

**Review aids.** Pending requests are listed oldest first with the waiting time. Each pending card shows the categories the seller already sells in, and quick notes that fill the rejection note. A banner offers a refresh when another admin decides a request or a new one arrives.

**Admin page states.** Loading: a card-shaped skeleton on first load and tab changes, button spinners while a decision is sent. Empty: one message per tab, with a link to pending requests when the other tabs are empty. Errors: an already-reviewed request tells the admin to open the Approved or Rejected tab. Verified by static review only; no browser walkthrough was run.

**31 October 2026 (list and dialog).** Replaced the card grid with a compact list. **Approve** is one click in the dialog; **Reject** first asks for the note. Loading uses a list-shaped skeleton. Not verified in a browser.

**Status cards.** The Pending / Approved / Rejected controls are count cards matching Registrations and User management; each is a link to that status.
