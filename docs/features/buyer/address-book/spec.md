# Buyer Address

**Status:** Partial; map pin and preferred provider added 29 October 2026
**Reviewed:** 29 October 2026

## Current behavior

Buyer profile stores province, municipality, barangay, street, and ZIP; checkout formats a default address from profile data. On the 6 October checkout page this registered address is the first and default choice. A Buyer can add another address in a dialog for the current order; it is held in `sessionStorage` for the tab (two hours), can be removed, and is submitted through the existing `shipping_*` fields. It is not saved on the server.

## Map pin and preferred provider (29 October 2026)

Account Management > Profile now has a **Map pin** card. It opens a Geoapify map dialog only when used: the Buyer can search an address, tap or drag the pin, or use the device location. The dialog reverse-geocodes the point and shows the **formatted address**, not latitude/longitude. The nearest form's street, ZIP, province, municipality and barangay fields are filled from the map and remain editable, so the typed address is always the submitted one. Save changes stores `buyer_details.latitude`/`longitude`. Checkout uses the saved pin as the registered address's `shipping_latitude`/`shipping_longitude`, so the pin does not have to be captured again for each order. The pin is optional and shared only with the assigned Rider.

Buyer, Seller and Logistics registration address steps use the same map component and store coordinates in `buyer_details`, `seller_details` and `logistics_centers` respectively. Geoapify is configured only in the deployment environment; no key is committed.

Settings > Delivery preference stores one optional preferred Logistics Company in `buyer_details.preferred_delivery_company_id`. Only Companies with an active Station Hub in the Buyer's municipality are listed. Checkout preselects it when the same Company serves both the Seller and the Buyer; otherwise the lowest quoted fee is preselected. The Buyer can always change the provider for an individual order.

These fields are additive in `2026_10_29_120000_add_delivery_map_and_preference_to_buyer_details.php` and `2026_10_29_130000_add_address_coordinates_to_seller_and_logistics_details.php`; the owner must run them.

## Gaps and acceptance direction

No multi-address book is saved on the server: extra addresses disappear on another browser or device, and there is no way to edit or set a new default. The map pin is a single point for the registered address, not a pin per saved address. The map needs a Geoapify API key and network access; without a key the dialog explains the missing configuration and the typed address still works. Reverse geocoding can be approximate, so filled fields must be reviewed. A `buyer_addresses` table, Buyer-scoped routes, and server-side revalidation are needed ([backend needs](../../../backend-needs.md), item 4). Current registration stores one combined street field and sets house number null.

## Source evidence

`app/Models/Profiles/BuyerDetail.php`, `app/Http/Controllers/Buyer/CheckoutController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).