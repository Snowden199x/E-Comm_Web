# Checkout and Order Creation

**Status:** COD checkout with server-quoted facility shipping and Buyer-chosen Logistics; owner migration and verification pending
**Reviewed:** 29 October 2026

## Current behavior

Checkout requires selected cart item IDs from the buyer's cart. The server validates their ownership and a checkout revision, validates street/barangay detail, a province/city choice from the bundled location catalog, and COD payment mode, locks the selected rows/products, checks stock and current product data, groups selected items by seller, creates order and item snapshots, decrements stock, and notifies each seller inside a transaction. Only purchased cart rows are removed; unselected rows remain. Buyer purchases do not create admin `new_order` notifications.
**Page layout (6 October 2026):** the page starts with a **Delivery address** section. The Buyer's registered address is the first choice, labelled Default and selected automatically; if it is incomplete it is disabled with a link to Account Management. **Add new address** opens a dialog (label, house number/street/barangay/ZIP, province, city) and the new address is selected for this order. Below are the items grouped by shop, the Cash on Delivery payment method, and a sticky order summary with Place order, which stays disabled until a usable address is selected. The form still posts the same fields: `items[]`, `checkout_revision`, `payment_mode`, `shipping_address`, `shipping_province_code`, and `shipping_city_code`, so server validation is unchanged. Extra addresses added in the dialog are kept only in the browser tab for up to two hours; see [Buyer Address](../address-book/spec.md).

After a failed submit the address the Buyer had chosen is kept as "Address you entered", and an accidental same-tab reload restores addresses added in the dialog for up to two hours. The current server-generated cart selection and checkout revision remain authoritative. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

Checkout accepts one optional note and one optional Seller voucher code per shop. Each note is saved only on that Seller's order. A code is quoted against selected cart lines and must be applied before submission; the server rechecks its eligibility and usage under a lock when creating orders. Orders retain the gross item subtotal, voucher discount, shipping fee, and Buyer payable amount separately. The checkout asks the server for one shipping quote per Seller order and recalculates it when the Buyer changes municipality. The server requires one unambiguous active origin and destination Station Hub, derives the real facility route, and charges ₱45 for up to two route stops plus ₱10 for every additional stop. It stores the fee, stop count, and facility route snapshot on the order. Order creation recomputes the quote inside the transaction and rejects a stale or altered fee. An address without a complete eligible route cannot be checked out yet. See [Buyer Voucher Usage](../voucher-usage/spec.md) and [Shipping Settlement](../../shared/commission-settlement/spec.md).

The Buyer can set a delivery pin from the map or from the browser's current location at checkout, and a pin saved in Account Management is prefilled for the registered address. The browser asks for permission; Vendo stores latitude/longitude on each seller-specific order through `2026_10_23_230000_add_shipping_coordinates_to_orders.php` and returns it only in the assigned local Rider's assignment. The pin is not inferred from municipality text or geocoded by a third party. Orders without a pin remain valid and the Rider app shows their address without a destination marker. The owner runs this migration before using the pin fields.

## Buyer-chosen Logistics provider (29 October 2026)

Each shop's shipping block now lists the Logistics Companies the Buyer may choose. A Company appears only when its own active Station Hub network has a Station Hub in **both** the Seller's municipality and the delivery municipality, so every choice can collect and deliver the parcel. The server builds the list (`CheckoutLogisticsOptionsService`) and returns it with each shop's quote; the UI shows the Company name, the real facility route, hub count, and fee. Options are ordered by fee, and the lowest is marked. The Buyer's preferred Company is preselected when it is in the list; otherwise the lowest fee is.

The chosen Company is posted as `logistics_company_ids[<seller id>]` and is revalidated inside the order transaction. Shipping is re-quoted for that Company; a changed fee or an altered Company list is rejected. The order stores `delivery_company_id`, the route snapshot, hub count, fee, rider pay rate, and COD amount for that route. `OrderRoutingService` routes with the chosen Company's origin and destination Station Hubs, falling back to the address match if the Company stops serving one end. Checkout now requires a Logistics choice for every shop; an address with no qualifying Company cannot be checked out.

## Gaps and acceptance direction

Payment gateway, taxes/fees, checkout idempotency key, and retry semantics remain future work. Weight and package dimensions do not affect the current shipping quote. Route coverage changes between checkout and Seller readiness can move a shipment into settlement review; no automatic repricing occurs. The Rider map displays the destination pin and current Rider GPS position, but does not calculate road routing or turn-by-turn directions.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Ecommerce/Order.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).

COD notice (28 October 2026): the checkout payment method card shows the live amount to pay in cash (items minus voucher plus delivery fee) and the order page shows a "have ₱X ready" line until delivery, using the order's saved `cod_amount`. New orders also save `cod_amount`, `money_status` and rate snapshots; see the [COD cash and delivery fee design](../../../design/cod-cash-and-delivery-fee-distribution.md).
