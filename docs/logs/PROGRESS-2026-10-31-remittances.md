# Progress 2026-10-31: Admin cash remittances page

Add this entry to `PROGRESS-2026-10-31.md` if you keep one log per day.

## Cash Remittances page: UI and UX pass

- **What changed.** Rebuilt `admin/remittances` on the admin design tokens (`vd-*`). Removed the orange, red, green and purple card borders, the `gray-*`, `amber-*` and `[#3b1735]` classes, and the `shadow-sm` card. Status chips follow the design system: pending = warning, discrepancy = danger, received and resolved = success.
- **Interaction.** The three status cards filter the table; search finds a reference or Company. **Confirm receipt** and **Resolve** open one dialog (replaces the inline `<details>` forms that stretched the row). The dialog starts at the expected amount, says what will happen, and the submit button reads **Record discrepancy** when the amount differs. Focus moves into the dialog and returns to the row button. Escape, the X and a click outside close it.
- **Errors.** Field errors say the fix. A failed save reopens the dialog with what was typed. A batch already handled by another admin (409) shows a page message with the fix instead of an error screen.
- **States.** Table skeleton after 150 ms, empty states (no batches, no match with **Clear filters**), and a load error with **Retry**.
- **Removed.** The "Confirmed batches" card (its count is on the Confirmed card), the long intro paragraph (now one line plus one footnote), and the uppercase table header.
- **Backend.** `CashRemittanceController`: status and search filters, one grouped query for the cards, fix-style validation messages, `decimal:0,2` on the received amount, 409 handled as a page message, shorter success messages. No route, migration or schema change, and `CashRemittanceService` is unchanged.
- **Docs.** `docs/features/admin/delivery-fees-and-remittances/spec.md` has a new paragraph. It also carries the Delivery Fees paragraph from the earlier zip, so extract either order.
- **Verified.** `php -l` on the controller; Blade compiled and the output linted (components stubbed); tag and directive balance. **Not verified:** the app itself (PHP here is 8.3, the project needs 8.4), `npm run build`, browser. Check: confirm an exact amount; confirm a short and an over amount without a note, then with one; resolve; open a batch another admin already handled; select each card, search, page; go offline and retry; a phone width; Escape and focus return.
- **Known gaps.** No date filter or export; the notes of confirmed (non-resolved) batches are not shown.
