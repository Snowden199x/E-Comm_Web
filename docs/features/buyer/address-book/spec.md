# Buyer Address

**Status:** Saved address book implemented in code (11 November 2026); migration `2026_11_11_120000_create_buyer_addresses_table.php` must be run by the owner; owner verification pending
**Reviewed:** 11 November 2026

## Current behavior

Buyer profile stores province, municipality, barangay, street, and ZIP; the registered address remains the account's own address. A Buyer can now also keep up to **10 saved delivery addresses** on `buyer_addresses` and choose one at checkout.

**Saved addresses page (`buyer.addresses.index`, Buyer > Account > Delivery > Manage addresses).** Each saved address has a name (Home, Office…, 40 characters), province, city or municipality, barangay, street, optional house number and ZIP, and an optional map pin. The first saved address becomes the default automatically; the Buyer can **Make default**, **Edit** (inline form on the same page), or **Delete** (two-step confirm). Deleting the default promotes the oldest remaining address. Province, municipality and barangay are canonicalized against the bundled PSGC catalog on the server; an invalid locality is rejected with a message that names the field. Every lookup is scoped to the signed-in Buyer, so another Buyer's address returns 404.

The address fields are the same map-capable set as Account Management: the Geoapify dialog fills the written fields and stores latitude/longitude per address, and saving without reopening the map keeps an existing pin.

**Account > Delivery** summarizes the saved addresses (count and default line) and links to the page. The registered address itself is still edited in Account > Profile and is not rewritten by saving a book entry.

**Checkout** lists saved addresses first (default preselected, labelled with the yellow *Default* chip), then the registered address as a fallback. The page posts `shipping_address_id` for a saved address; the server loads that row by the signed-in Buyer, rebuilds `shipping_address`, the province/city codes and the pin from it, and rejects the order if the address no longer resolves in PSGC. Text typed in the **Add new address** dialog for a single order still works as before and is not saved unless the Buyer adds it on the Saved addresses page (the dialog links there).

## Gaps and acceptance direction

- Registration still stores one combined street field and sets house number null; the first saved address is not created from it automatically.
- A new address typed at checkout is kept only in the browser tab for two hours, as before. Saving it requires the Saved addresses page.
- The preferred Logistics Company is still stored per Buyer, not per saved address, so a preferred provider is not tied to a specific delivery municipality.
- The map needs a Geoapify API key and network access; without a key the dialog explains the missing configuration and the typed address still works. Reverse geocoding can be approximate, so filled fields must be reviewed.
- No address is verified against a real geocoder beyond the PSGC locality check; there is no pincode/barangay boundary validation.

## Source evidence

`app/Models/BuyerAddress.php`, `app/Http/Controllers/Buyer/AddressBookController.php`, `app/Http/Controllers/Buyer/CheckoutController.php`, `app/Http/Controllers/Buyer/AccountController.php`, `resources/views/buyer/addresses/index.blade.php`, `resources/views/buyer/addresses/partials/fields.blade.php`, `resources/views/buyer/checkout.blade.php`, `resources/views/buyer/account.blade.php`, `routes/web.php`.

## Related documentation

See [checkout orders](../checkout-order/spec.md), [Buyer account](../account-management/spec.md), [domain status](../../../domain-feature-status.md), and the [11 November progress log](../../../logs/PROGRESS-2026-11-11.md).
