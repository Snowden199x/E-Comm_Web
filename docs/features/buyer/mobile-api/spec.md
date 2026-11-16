# Buyer Mobile API

**Status:** Web API and the matching Flutter screens are written in code; owner verification pending. The Flutter client lives in the separate `E-Comm_Mobile/vendo_buyer` repository.

**Reviewed:** 15 November 2026

The Buyer app previously had no server API: its sign-in called `/api/v1/buyer/*` routes that did not exist and its storefront screens used sample data. This adds a scoped buyer API under `routes/api.php` and connects the app to it. The web buyer pages keep their session login and Blade views.

## Authentication

- `POST /api/v1/buyer/login` `{email, password, device_name}`: requires a real buyer account that is approved, unarchived and active. Returns a Sanctum bearer token (`buyer:shop` ability, 30 days), `expires_at` and the buyer profile. A wrong password returns 401; a pending, disapproved, archived or inactive account returns 403 with the same wording as the web login; a non-buyer account is refused with "This login is for buyer accounts only."
- `GET /api/v1/buyer/me` and `POST /api/v1/buyer/logout` (revokes the current token only).
- `POST /api/v1/buyer/email/otp/send` `{email}`: rejects an email that already has an account (422), stores a hashed six-digit code in the cache for 10 minutes, then mails it. Five requests per email per minute.
- `POST /api/v1/buyer/email/otp/verify` `{email, code}`: returns `verification_token` (30 minutes) that the register call must send. Limited to ten attempts per email per minute.
- `POST /api/v1/buyer/register` (multipart): the same fields, rules, ID files and `pending` status as the web buyer registration, plus `verification_token` bound to that email. Files go to the private `local` disk (`valid-ids/`); a failed transaction deletes them. Returns 201 and the account status.
- **Registration address.** `province`, `municipality` and `barangay` are names chosen from the same bundled PSGC snapshot that `/locations` serves, so the app never asks for free text. The server re-resolves the province and city with `LocationCatalog::codesForNames()` and rejects a pair that does not exist (422 on `municipality`); it then confirms the barangay belongs to that city (422 on `barangay`). `street` and `zip_code` remain free text.
- `POST /api/v1/buyer/password/otp/send|verify|reset`: buyer-only password reset. The send call always answers 200 so an unknown email is not revealed, and only buyer accounts receive a code. A successful reset revokes every stored token for that buyer.

Every protected route runs `auth:sanctum` plus `EnsureApprovedBuyerToken`, which re-checks role, approval, archive flag and account status on every request, mirroring the web `EnsureActiveBuyer` middleware.

## Storefront

- `GET /home`: top-level categories, up to five announcements for buyers, and 18 recommended products with card metrics.
- `GET /categories`: all categories with their children, in the same order the web Categories page lists them.
- `GET /products?category_id&search&sort&page`: paginated 12 at a time; `category_id` includes the category's children.
- `GET /products/{product}`: full detail (images, variants, specifications, seller, description) plus up to eight recommended products from the same parent category. Unavailable products and sellers return 404.
- `GET /sellers?search&page` and `GET /sellers/{seller}?search&sort&page`: approved, active sellers and their approved products.
- `GET /locations` and `GET /locations/{cityCode}/barangays`: bundled PSGC data for the registration and checkout address forms. `/locations` returns every province with its cities/municipalities and codes in one response (82 provinces); the barangay call returns code/name pairs for one city and 404s an unknown city. Both are public and rate limited to 30 requests per minute.

All listing queries use `Product::availableToBuy()`, so a product from an unapproved, archived or inactive seller never appears.

## Saved items and cart

- `GET /saved-items`, `POST /saved-items/{product}`, `DELETE /saved-items/{product}`, `DELETE /saved-items`: the same 60-product wishlist as the web heart buttons, pruned when a product becomes unavailable.
- `GET /cart`, `POST /cart` `{product_id, quantity, color, size, variant_id}`, `PATCH /cart/{cartItem}` `{quantity}`, `DELETE /cart/{cartItem}`: the web cart rules (approved product and seller, required variant when the product has variations, colour/size must be offered, quantity cannot pass the stock). Each response returns the whole cart — lines, groups per shop, totals and "also like" — so the app never computes money.

## Checkout

- `POST /checkout/preview` `{items:[cartItemId]}`: the selected lines, cart groups, per-shop totals, available vouchers, the saved delivery address with its PSGC codes, and `checkout_revision`.
- `POST /checkout/shipping-quote` `{items, shipping_province_code, shipping_city_code}`: one quote per seller plus the total. The quote still needs an active Station Hub for both endpoints (422 otherwise).
- `POST /checkout/voucher-quote` `{items, seller_id, code}`: the discount for one shop voucher.
- `POST /checkout` `{items, shipping_address, shipping_province_code, shipping_city_code, payment_mode:"cod", checkout_revision, seller_notes?, voucher_codes?}`: places one order per seller. The mobile API does **not** accept client-supplied shipping amounts; the server recomputes every quote. It returns 201 with the created orders.

`BuyerCheckoutService` now holds the checkout transaction (locks, stock, revision check, voucher redemption, COD amount, rate snapshots, inventory movements, seller notification, cart cleanup) and is called by both the web `CheckoutController@store` and the mobile API. `CheckoutRevisionService` holds the revision hash used by both. Web behaviour is unchanged: it still validates the posted `shipping_quotes` before calling the shared service.

## Rate limits

Every protected route is capped at 120 requests a minute per Buyer, and the endpoints that need a tighter cap keep their own bucket: `POST /checkout` 10/min, shipping and voucher quotes 20/min, geo lookups 30/min, return requests 10/min and chat writes 20/min. Each `throttle` in the group carries a prefix (`buyer`, `buyer-checkout`, `buyer-shipping-quote`, …) because Laravel resolves a throttle key from the authenticated user alone: without the prefix the group counter (every API call) and the endpoint counter are the same bucket, so ten ordinary calls in a minute made `POST /checkout` answer 429 *Too Many Attempts* before the order was ever submitted.

## Orders, notifications and account

- `GET /orders?status&page` (15 per page), `GET /orders/{order}`, `PATCH /orders/{order}/cancel` `{expected_status, reason, reason_details?}`, `POST /orders/{order}/complete`, `POST /order-items/{orderItem}/review` `{rating, comment}`. Every lookup is scoped to `buyer_id`; another buyer's order returns 404. Cancellation uses `OrderCancellationService` with the web's reason list and status guard. Completing is allowed only for `delivered`.
- `GET /notifications?filter=all|unread`, `POST /notifications/{notification}/read`, `POST /notifications/read-all`: only the signed-in buyer's rows. Each row carries `product` (name and image of the parcel's first item) and `order` (`{id, number, status, status_label}`) resolved from the notification's link. `order` is null for announcements, support replies and reviews; the app labels an order card from `order.status` and opens that order, so "…: Completed" no longer reads as an order that was only placed.
- `GET /account`, `PUT /account` (name, phone, street, house number, ZIP), `PUT /account/password`.

## Google sign-in, vouchers, reviews, help and legal

- `POST /api/v1/buyer/google` `{credential, device_name}`: verifies the Google ID token with `GoogleIdTokenVerifier` and answers with the same session as `/login` (token, `expires_at`, buyer profile). Google never creates an account: an email with no Buyer account gets 404 and the message to register first, another role gets 409, and a pending, disapproved, archived or inactive buyer gets the same 403 wording as the password login. An unverified email is marked verified on the way in, because Google already proved it.
- `GET /vouchers`: every active shop voucher from an approved, active Seller, shaped as `{data:{id, shop_id, shop, name, code, discount_label, scope_label, min_spend_label, max_discount_label, valid_label, uses_left, shop_uses_left, usable, unavailable_reason}}`. The server writes the labels (percent or peso discount, scope, minimum spend, cap, validity) and says why a voucher cannot be used — not started, shop use limit reached, or this buyer's own limit reached. `uses_left` counts what this buyer has left; `shop_uses_left` is the shop-wide remainder.
- `GET /reviews`: the buyer's own product reviews, newest first, with `product_name`, `image`, `shop`, `rating`, `comment`, `visibility`, `created_at_label` and the Seller's `reply` when there is one. Writing a review stays on `POST /order-items/{item}/review`.
- `GET /help`: the FAQ the app's Help screen renders — `title`, `intro`, `contact_email` from `mail.from.address`, and `groups[{title, items[{question, answer}]}]`. The copy describes the behaviour this API actually implements (COD, delivery window and reschedule, returns, voucher stacking limits, one review per item, account deletion through support).
- `GET /legal/{page}` `| page = terms|privacy`: reads the same `docs/legal/terms-and-conditions.md` and `privacy-policy.md` the web pages render, flattens inline Markdown and returns `{title, updated_label, blocks[{type, text, level}]}` where `type` is `heading` (level 1-4), `paragraph` or `bullet`. `updated_label` is the file's modified date, so the app cannot drift from the published page. An unknown page or a missing file answers 404.

## Flutter client changes

- `lib/core/api/buyer_api.dart` now calls every endpoint above and stores the token in `flutter_secure_storage` as before. `lib/core/api/api_models.dart` holds the typed payloads.
- Splash opens the storefront when a token exists instead of always showing the login screen.
- Home loads categories and products from `/home` with real photos, opens `ApiProductScreen`, and the quick actions open Orders and Saved Items.
- `ApiProductScreen` (new) shows real images, variants, stock, shop and the Saved heart; `ApiSellerScreen` and `CategoryProductsScreen` (new) list a shop's or category's products.
- Cart is API-backed (quantity changes and removal call the server) and its checkout button opens the new COD `CheckoutScreen`. The cart is always a pushed route and its header back arrow pops it (`Navigator.maybePop`), returning to Home, the product page or the seller page that opened it; the Edit, chat and voucher rows remain visual placeholders.
- `RegisterScreen` loads provinces on open and shows province, municipality/city and barangay as pickers fed by `/locations` and `/locations/{cityCode}/barangays`; changing the province clears the city and barangay, and changing the city reloads the barangays. The review step prints the selected names. A failed list request shows a retry message instead of an empty form.
- Saved Items, Orders (list and detail with cancel and "order received"), and Notifications are new or rewritten API-backed screens; Profile links to them. Saved Items is both the second bottom-nav tab and a pushed route, so its header shows a back arrow only when the route can pop (`Navigator.canPop`), while Orders, checkout and Notifications rely on the default `AppBar` back button.

Not wired in the app yet: voucher selection at checkout (the API accepts codes, the screen does not offer them), writing a product review from an order, profile editing/password change, and paging beyond the first page of lists. The earlier sample-data product detail and seller screens remain in the repository but are no longer opened by the live storefront.

## Verification

- The endpoints were exercised against the local database with a real buyer token: home, categories, products, product detail, sellers, seller detail, saved-items add/remove, cart, checkout preview, shipping quote (422 on the local data, see below), voucher quote (422 for an unknown code), orders list/detail, notifications read-all, account, and the 401/404 authorization paths. The temporary token was deleted afterwards.
- The location endpoints were exercised directly through the controller: `/locations` returns 82 provinces with nested city codes, Abra/Bangued returns 31 barangays, and an unknown city answers 404. `codesForNames()` resolved province/city pairs for an ordinary province and for Metro Manila → Quezon City, and returned null for a city that does not belong to the province; the registration address check builds on that result.
- The test data has no active Province Hub, so a complete Station → Province → Station route cannot be resolved and the shipping quote correctly answers 422; checkout placement needs an environment with full coverage.
- `dart analyze` reports no errors or warnings in the buyer app (only pre-existing style infos). The app was not run on a device or emulator, no order was placed, and no page was visually reviewed.
