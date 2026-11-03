# Progress 2026-10-31: Admin commission page

Add this entry to `PROGRESS-2026-10-31.md` if you keep one log per day.

## Commission page: UI and UX pass

- **What changed.** Rebuilt `admin/commission` (index, summary cards, table, seller dialog) on the admin design tokens (`vd-*`): plum family only, with success and danger pastels used for the change chips and the error banner. Removed the amber, sky and green card colours and the raw hex values.
- **Interaction.** Added a **View breakdown** button on each row, **Edit rate** on the rate card, **Go to this month**, **Retry**, **Clear search**. The next-month arrow is disabled on the current month. The dialog moves focus to its close button and returns it to the row button.
- **States.** Skeletons for cards, table and dialog (shown after 150 ms); empty states for no sellers, no search match, no sales in the month and no sales in the dialog; error messages that say the fix.
- **Removed.** The "Seller accounts" card (replaced by Sellers with sales), the chevron column, the helper line beside the search, the long formula paragraph (now one line), the dialog chart and the dialog Close button.
- **Backend.** `CommissionController`: one ranked query instead of one query per seller, month validation, `average_rate` in the seller JSON, 404 for non-sellers. No route, migration or schema change.
- **Docs.** `docs/features/admin/commission-settlement/spec.md` has a new section.
- **Verified.** `php -l` on the controller; Blade compiled and the output linted (components stubbed); tag and directive balance; the ranking SQL pattern run on SQLite with sample rows. **Not verified:** the app itself (PHP here is 8.3, the project needs 8.4), `npm run build`, browser. Check: switch months fast, search, page through, open a seller, open one with no sales, go offline and retry, a phone width, Escape and focus return.
- **Known gaps.** The seller table still shows suspended and deactivated sellers; there is no export and no settlement ledger (see the spec).
