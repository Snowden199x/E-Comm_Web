# Admin design system

Rules for every Admin page. Applied page by page; **Dashboard** is done first (31 October 2026).

## Palette

Defined once as `theme.extend.colors.vd` in `tailwind.config.js` (Tailwind: `bg-vd-plum`) and as `--vd-*` in `resources/css/admin/motion.css` (plain CSS, Chart.js). Do not add colours without updating both and this page.

| Role | Tokens |
|---|---|
| Text | `ink` #2B1730, `muted` #6B5D70 |
| Surfaces | `paper` #FBF7F2, white, `tint` #F7F1F7, `lavender` #E4DAEA |
| Brand and primary action | `plum` #3B1735, `plum-hover` #4D1F45, `plum-soft` #CDB9D4 |
| Borders | `line` #ECE4EC, `line-strong` #DDD0E0 |
| Success (approve, resolved, up) | `success-bg` / `-line` / `-ink` |
| Warning (waiting, open) | `warning-bg` / `-line` / `-ink` |
| Danger (reject, error, down) | `danger-bg` / `-line` / `-ink` |

Accent tints (`peach`, `sand`, `mauve`, with solids `clay`, `gold`, `orchid`) are for the four Dashboard stat cards and the icon chips of the filter cards (`admin/partials/filter-stat-cards`).

Green and red appear only for approve/reject, status and errors. Everything else is plum family.

## Rules

- **Borders, not shadows.** Cards are white with a 1px `line` border. Modals and menus use borders too.
- **Buttons start with a verb** ("Approve", "Reject registration", "Open full report", "Create announcement"). Primary = solid plum, one per area. Secondary = bordered white. Approve/Reject = pastel success/danger fill with a border.
- **Spacing.** Card padding `p-5`; gap between cards `gap-5`; page padding `p-4 sm:p-6 lg:p-7`; list rows `py-1` around a `py-2` hit area.
- **Errors say the fix.** "Pick a reason to reject this registration." Not "The reason field is required."
- **Copy.** Sentence case. Short. No "yet", "please", "successfully".
- **Empty states.** `<x-admin.empty-state>`: static icon chip, title, one line, optional action. Use `filtered` plus a "Clear filters" button when a filter is active.
- **Loading states.** Page: boot and navigation skeletons (`x-admin.skeleton`). Buttons: spinner from `button-loading.js`. In-card data: `.vd-skeleton` block until ready.
- **Filters.** One control per job: if cards filter by type, there is no type dropdown. A "Clear filters" button appears only while a filter is active. Selecting the active card again clears it.
- **Account status.** Active = success, temporary hold (suspended) = warning, switched off (deactivated) = neutral, blocked or rejected = danger. Destructive buttons use the danger pastel; restoring uses the success pastel.
- **Tables.** White card, 1px `line` border, `tint` header row, `py-3` rows, one neutral chip for type, a bordered "Review" button per row. Row click is a convenience, never the only way in.
- **Feedback.** `<x-admin.flash>` under the page heading. Success closes after 6 seconds; errors and warnings stay.
- **Popups.** `<x-admin.dialog>` is the one popup: title, one line, close button, Esc and outside click close it, white panel with a 1px border. `<x-admin.confirm-dialog>` is the yes/no version: Cancel on the left, the action on the right in the pastel tone of its meaning (danger, warning, success). Do not build a popup by hand.
- **Forms.** Label above the field (`text-xs text-vd-muted`), `h-10` fields with a `line-strong` border, the error under the field in `danger-ink`. The error says the fix ("Use at least 8 characters."). When a popup form fails, the page reopens that popup with the old input. Cancel on the left, the main action on the right.
- **Status pills.** `<x-admin.status-pill tone="success|warning|danger|plum|pending|neutral">` is the one pill for table statuses. Green = fine, salmon = needs attention, deep red = blocked or suspended, plum = special, amber = waiting, grey = inactive. Do not hand-roll pill colours.
- **Compliance cards.** Seller Compliance count cards use the Registration card shape with a tone per meaning: green = compliant, salmon = warnings, deep red = violations, plum = suspended. Each card links to its tab.
- **Row actions.** Every table row has a bordered verb button ("View", "Review", "Activate") that fills plum on hover. Clicking the row is a shortcut only.
- **Review cards.** When each item needs a document, a note and a decision, use one white card per item in a two-column grid (`xl:grid-cols-2`): identity and status chip on top, facts as chips, a secondary button for the document, and a `tint` footer holding the decision form. Approve is one click; Reject asks for the reason and checks it in the browser first, with the error under the field.
- **Status tabs.** When one status must always be chosen, use tabs with counts (plum = current), not count cards. Links swap `#main-content` and use the matching skeleton (`data-skeleton`).
- **Pagination.** In-place tables use `data-page` buttons; server-rendered pages pass `asLinks => true` to `admin/partials/pagination`.
- **Documents.** Open private files in the shared popup (`$dispatch('open-document', {...})`), never in a new tab.
- **Toolbar.** Search, date and one dropdown use the shared pills (`admin/partials/filter-toolbar`; pass `hideDate` when there is no date to filter by). The dropdown filters by user type by default; pass `dropdownModel`, `dropdownOptions`, `dropdownAll` and `dropdownLabel` to filter by something else (Messages uses status).
- **Inbox (two panes).** A bordered list on the left (header row, one row per item, a type pill, an unread count) and the detail on the right; the empty pane says what to do ("Select a message to view"). On phones the detail replaces the list and has a back button. Background refreshes are silent; only user-triggered loads dim the list and show the progress bar.
- **Chat.** Own messages = solid plum bubble, others = `tint` bubble with a border. The composer is a pill input plus a "Send" button; an attachment is staged as a removable chip, never sent on pick. Errors appear above the composer and say the fix.
