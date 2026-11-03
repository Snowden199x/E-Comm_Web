# Progress 2026-10-31: Admin delivery fees page

Add this entry to `PROGRESS-2026-10-31.md` if you keep one log per day.

## Delivery Fees page: UI and UX pass

- **What changed.** Rebuilt `admin/delivery-fees` on the admin design tokens (`vd-*`): plum family, success and danger pastels only for banners and errors. Removed the green, purple, orange and yellow card borders, the gray and `#3b1735` classes, and the `shadow-sm` cards.
- **Rates form.** Inline validation with messages that say the fix. **Save rates** is disabled until a rate changes and is valid. The footer shows the pending change ("Seller commission 10% → 12%"), with **Discard changes**. Non-super admins see disabled inputs and a short note. The preview follows what is typed and shows "—" while a rate is invalid.
- **Table.** Right-aligned amounts, Vendo emphasized, "Needs review" as a warning chip. It pages in place (typed rates are kept) with a skeleton after 150 ms, an empty state, and an error with **Retry**.
- **Removed.** The "Seller commission now" card, the uppercase table header, and the long helper text.
- **Backend.** `DeliveryFeeController::update` validates with `decimal:0,2` and short fix-style messages, and the success message is shorter. No route, migration or schema change. `index` is unchanged.
- **Docs.** `docs/features/admin/delivery-fees-and-remittances/spec.md` has a new paragraph.
- **Verified.** `php -l` on the controller; Blade compiled and the output linted (components stubbed); tag and directive balance; tokens and icons checked against `tailwind.config.js` and the icon component. **Not verified:** the app itself (PHP here is 8.3, the project needs 8.4), `npm run build`, browser. Check: type an invalid rate, a valid one, then Discard; save; page through the table with an unsaved change; sign in as a non-super admin; go offline and retry; a phone width.
