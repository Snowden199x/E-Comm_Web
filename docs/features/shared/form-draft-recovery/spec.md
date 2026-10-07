# Web form reload recovery

**Status:** Implemented in code for the listed web forms; owner browser verification pending.
**Updated:** 4 October 2026

## Current behavior

Unfinished form values are saved in the browser tab's `sessionStorage` while the user types. A reload in the same tab restores drafts saved in the last two hours. The first reload that restores a given draft shows one small, dismissible notice at the top right; it closes automatically after seven seconds. Later reloads and ordinary navigation, including returning after login, restore the draft silently. Only one draft notice appears per page load, even when several forms have saved values. The notice marker clears when the draft is saved successfully or expires. Product and AJAX drafts clear after a successful response; ordinary native forms clear on submission. Keys include the role and authenticated user, plus the product, order item, conversation, policy, or announcement where relevant. The server still validates every submission; browser drafts grant no access and do not override current cart items, checkout revision, product revision, or other server-owned fields.

Buyer, Seller and Logistics Center registration retain ordinary identity, address and business fields. Buyer registration already had reload recovery; Seller recovery was extended and Logistics Center recovery was added. All three return to the first step after reload so passwords and identity/business documents can be re-entered. Email OTP or Google proof remains subject to the existing server/session verification rules. Consent must be checked again.

Seller Add/Edit Product has a dedicated draft because category attributes, specifications, variation types/options/prices, media order and removal choices live in JavaScript state. The Product form uses only this handler and shares the one-time side notice behavior. Its draft restores text, selections, extra specifications, partial variation configuration and existing product-image order. An edit draft is discarded if the product revision has changed. New photo/video/variant image files cannot be restored after reload and must be selected again. A browser draft is separate from the database **Save as Draft** action; only a successful server save clears the browser copy.

The shared ordinary-form recovery is enabled for Buyer checkout and profile, Buyer product review, Seller account, restock, order decline, shipment cancel/tracking, feedback reply/report and support messages, Buyer/Seller marketplace messages and account reports, Logistics Rider rejection and pickup-decline reasons, Admin announcement create/edit, policy edit, welcome message, account creation, registration rejection, suspension, complaint decisions, product moderation and review moderation. Buyer Vendo Support's Alpine composer saves its message text separately. Forms loaded later into a Seller drawer are also detected. AJAX forms clear their drafts only after a successful response. Native form submissions clear the local draft at submit; Laravel's `old()` values remain the fallback if server validation redirects back. Checkout restores province before rebuilding the city list, then restores the selected city.

## Limits

Files, passwords, OTPs, CSRF tokens and server-owned hidden values are never stored. The Admin policy editor explicitly stores its HTML content through its existing hidden field. `sessionStorage` is tab-specific and may be unavailable in browser privacy modes. Closing the tab, clearing site data, or moving to another device does not preserve these drafts. This is reload recovery, not a server-side autosave or a guarantee against browser/device failure. One-click actions, filters and password-reset forms do not use draft recovery; destructive actions that require a written reason are included only where listed above.

## Source

`resources/js/shared/draft-notice.js`, `resources/css/shared/draft-notice.css`, `resources/js/shared/form-drafts.js`, `resources/js/seller/products.js`, the three registration views/scripts, and opted-in Blade forms.
