# Station Hub Cash Collections

**Status:** Web workflow written in code; migrations and owner verification pending. Part of the [COD cash and delivery fee design](../../../design/cod-cash-and-delivery-fee-distribution.md).

Station Hub staff see the COD cash their Riders collected at delivery and confirm it. Nothing here moves real money: Vendo has no bank or e-wallet payout, so every step is a confirmation recorded in the system.

**Cash Collections** (`GET /logistics/portal/station/cash`, `lgp.station.cash`) shows four figures (cash held by Riders, verified cash ready to send, open discrepancies, cash sent and awaiting the Province Hub) and a Rider cash ledger. For each Rider of this Station the ledger shows the parcels holding cash, cash on hand, cash in discrepancy, and earnings accrued from `order_shipping_earning_lines` (paid out by the Company, not here). A Rider who no longer belongs to this Station but still holds cash from it is not listed and cannot be received here.

**Receive cash** (`POST .../cash/riders/{courierDetail}/receive`): staff pick the parcels the Rider handed in and enter the amount received. Only orders in `collected_by_rider` for that Rider and this Station can be included; the orders are locked, so two staff cannot receive the same parcel twice. If the amount equals the sum collected, a `cash_remittances` row (`rider_to_station`, status `received`) and its items are written and the orders move to `remitted_to_station`. If it differs, a note is required, the remittance is `discrepancy`, and the orders move to `discrepancy` (held). A parcel can appear in a `rider_to_station` remittance only once (unique `order_id`, `level`).

**Cash Discrepancies** (`GET .../cash/discrepancies`, `PATCH .../cash/remittances/{remittance}/resolve`): lists discrepancies for this Station, open ones first, with Rider, parcels, expected, received, difference and the receipt note. Resolving requires a note, records who resolved it and when, sets the remittance `resolved`, and returns its orders to `remitted_to_station` so their cash counts in full. Who bears a shortage (Rider, Company or write-off) is a business decision outside the system; the note records the outcome.

**Send to Province Hub** (`POST .../cash/remit`): batches every parcel in `remitted_to_station` for this Station that is not yet in a `station_to_province` remittance into one `pending` remittance addressed to the parent Province Hub, with the total. A parcel can be in only one such batch. The orders stay `remitted_to_station` until the Province Hub confirms receipt (see the [Province Hub and Company cash spec](../province-company-cash/spec.md)), so the batch remains `pending` until then. Sending requires an active parent Province Hub.

Access is limited to the Station Hub portal (`EnsureLogisticsPortalRole:station`). A Rider must belong to the signed-in Station (`courier_details.station_hub_id`) and the Rider's cash is matched on the collecting Station, so staff cannot act on another Station's cash.

Migration `2026_10_28_130000_add_resolution_to_cash_remittances.php` adds `resolution_note`, `resolved_at` and `resolved_by`; it depends on the step 2 cash tables.
