# Logistics Portal Design System

**Updated:** 13 October 2026
**Applies to:** the Company / Province Hub / Station portal pages (`resources/views/logistics/company/*`, `hub/*`, `shared/account.blade.php`, `components/logistics/shell.blade.php`)
**Method:** derived by hand from the [UI UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) workflow (product type, pattern, style, colour, typography, anti-patterns, pre-delivery checklist). The skill's CLI and CSV data were not run; this repo's rule is to keep the existing Vendo visual language, so the colour and type choices below record what the portal already uses rather than a new palette.

## Product type

B2B operations console for a logistics company: dense tables, queues of parcels that need a decision, bulk actions, and per-role scope (company, province hub, municipality station). Users are hub managers working through a queue, often on a laptop beside a scanner or on a phone at the dock.

## Pattern

**Action-first dashboard.** Every role dashboard answers "what needs me now?" first (an attention list that links to the page where the work is done), then the pipeline stages with live counts, then supporting tables. Work pages use one of three shapes:

| Shape | Used by | Rule |
| --- | --- | --- |
| Queue table with row action | Incoming, Linehaul | One primary action per row; destructive or reversible-with-reason actions open a dialog |
| Select + bulk bar | Parcel Sorting, Delivery Assignments | Checkbox column with select-all; a sticky bar appears only when something is selected and holds the required choice (destination or rider) |
| Directory with tabs | Rider Management, Hub Managers, Hubs | Search, state tabs with counts, confirm before deactivating, reason required before rejecting |

## Style

Keep the existing Vendo look: warm off-white canvas, plum sidebar, gold active marker, white cards with a 1px warm border, 12-16px radii, soft shadow only on hover. No gradients on content, no glass, no decorative illustration. Icons are inline SVG from `components/logistics/icon.blade.php` (never emoji).

## Colour (existing `--lg-*` tokens, do not add new brand colours)

| Role | Token | Hex |
| --- | --- | --- |
| Ink / body | `--lg-ink`, `--lg-ink-2` | #1c1620, #4a424e |
| Muted text | `--lg-muted` | #6f6873 (4.5:1 on white) |
| Brand | `--lg-brand`, `--lg-brand-dark` | #5a1d63, #43104a |
| Accent (CTA on dark, active marker) | `--lg-gold` | #c8962e |
| Success / Warning / Danger / Info | `--lg-green`, `--lg-amber`, `--lg-red`, `--lg-blue` | #1f7a4d, #9a6700, #b42318, #1d5fa8 |

Status is never colour-only: every pill has a text label and a leading dot, alerts carry an icon, and capacity meters print their number beside the bar.

## Typography

Poppins (already loaded by the shell). 14px body, 13px table headers and helper text, 12px pills and hints, 16px section titles, 24px page titles. Numbers in tables use `is-num` (right aligned) and tabular spacing.

## Spacing and layout

8px grid. Page gap 24px, card padding 20-24px (16px under 720px). Content max width 1320px. Tables scroll inside `.lg-table-wrap`; under 720px tables that carry `lg-table--stack` and `data-label` on each cell turn into stacked cards.

## Interaction rules

- Every clickable element has a visible `:focus-visible` ring and a 40px minimum control height; icon-only buttons have an `aria-label`.
- Buttons that need a choice stay disabled until the choice is made, and say why in nearby text.
- Rejecting, returning to seller, and deactivating ask for confirmation; rejecting and returning also require a typed reason (5+ characters).
- Results are announced in a polite `aria-live` toast region (`lg-toast` event on `window`); toasts auto-dismiss after 5s and can be closed.
- Empty tables show `x-logistics.empty` with the next step, not a blank grid.
- Forms: visible labels, required marker, inline error under the field (`aria-invalid`, `aria-describedby`), first invalid field gets focus.
- Motion is limited to 150-400ms transitions and respects `prefers-reduced-motion`.

## Anti-patterns to avoid here

Emoji icons, colour-only status, hover-only actions, truncated text with no full value, tables that force horizontal scroll on a phone with no alternative, modals without Escape/backdrop close, purple-pink gradients, decorative motion.

## Pre-delivery checklist

- [x] No emoji used as icons (inline SVG only)
- [x] Pointer cursor and focus ring on every interactive element
- [x] Text contrast 4.5:1 or better using existing tokens
- [x] `prefers-reduced-motion` respected (workspace.css global rule plus an explicit rule for toasts, which render outside `.lg-body`)
- [x] Skip link to `#main-content`, landmark `nav`/`main`, toast region is `aria-live`
- [x] Tables have `data-label` stacking for small screens
- [x] Status shown by text plus dot, not colour alone
- [ ] Verified in a browser at 375 / 768 / 1024 / 1440px (owner testing pending; see the progress log)

## Page notes (overrides)

- **Company dashboard / Hubs:** hierarchy is Company > Province hub > Station; stations are indented with a left rule under their province hub.
- **Hub dashboards:** the pipeline strip differs by role (station: Incoming, Sorting, Assign, Out for delivery; province: From stations, Sorting, Linehaul, Arriving).
- **Delivery Assignments (station only):** parcels in one delivery area go to one rider per assignment, matching the one-area-per-rider rule in the logistics navigation spec.
- **All role pages still run on hard-coded preview data** (`routes/logistics_preview.php`). Interactions change page state only; nothing is saved.
