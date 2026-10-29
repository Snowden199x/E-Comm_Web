# Vendo Project Documentation

This documentation describes the Laravel web application as it exists in the repository, checked on **23 October 2026**. It distinguishes working code from partial flows, UI placeholders, and future work so that teams do not treat a mock screen as a finished feature.

## Start here

- [Codex working rules](../AGENTS.md) — repository workflow, documentation requirements, role scoping, and the owner's testing preference.
- [Workspace guide](Development-Workspace-&-Task-Planning-guide.md) — local setup, repository boundaries, and contribution rules.
- [Current implementation status](domain-feature-status.md) — the cross-domain source of truth for what is implemented.
- [Feature implementation guide](feature-implementation-guide.md) — how a feature moves from UI through routes, authorization, database, and tests.
- [Vendo Web test suite](testing.md) — test command, SQLite isolation, role-level coverage, and known limits.
- [Architecture](architecture.md) and [schema](schema.md) — current technical structure and data relationships.
- [Security overview](security.md) — code-based web/API controls, deployment configuration, and known security gaps; not a penetration-test report.
- [Terms and Conditions](legal/terms-and-conditions.md), [Privacy Policy](legal/privacy-policy.md), and [policy release review](legal/release-review.md) — source for the public web pages and remaining consent/privacy follow-ups.
- [Order and logistics flow decisions](order-logistics-flow-decisions.md) — current order states and ownership boundaries.
- [Province Logistics network and stations](features/logistics/province-network-and-stations/spec.md) — current Company / Province Hub / Station Hub model, implemented portal slices, migration notes, and remaining operations. The [backend restructure spec](design/logistics-hub-restructure-backend-spec.md) and [23 October pull review](design/2026-10-23-logistics-pull-review.md) track the pulled UI work. The [virtual SH routing](features/logistics/subhub-routing/spec.md) and [SH scanner API](features/logistics/sh-scanner-api/spec.md) specs describe preserved legacy behavior. See also [Main Hub route plans](features/logistics/hub-to-hub-routing/spec.md) and [truck/linehaul dispatch](features/logistics/company-truck-linehaul-dispatch/spec.md).
- [Rider registration API](features/courier/registration-api/spec.md), [rider scan API](features/courier/scan-api/spec.md), [Logistics navigation](features/logistics/navigation/spec.md), and [Logistics account management](features/logistics/account-management/spec.md) — current backend contracts and center account/sidebar scope.
- [Province Hub and Company cash remittances](features/logistics/province-company-cash/spec.md) — confirming cash batches up the chain, sending Vendo its share, and the Company Rider Payment setting.
- [Seller earnings](features/seller/earnings/spec.md) — per-order earnings after Vendo's commission, credited automatically once Vendo confirms the cash.
- [Admin delivery fees and cash remittances](features/admin/delivery-fees-and-remittances/spec.md) — editing Vendo's commission rates, delivery fee distribution history, and confirming cash from Logistics Companies.
- [Station Hub cash collections](features/logistics/station-cash-collections/spec.md) — receiving Rider COD cash, resolving discrepancies and sending verified cash to the Province Hub.
- [Buyer mobile API](features/buyer/mobile-api/spec.md) — the `/api/v1/buyer` token API (auth, storefront, saved items, cart, COD checkout, orders, notifications) and the Flutter buyer screens connected to it.
- [Parcel exception and detour workflow](features/logistics/parcel-exceptions/spec.md) and [Rider delivery-proof API](features/courier/delivery-proof-api/spec.md) — current web exception and new facility-route delivery evidence contracts; the Rider app still needs integration.
- [Google sign-in](features/shared/google-auth/spec.md) — verified web and Rider token exchange, registration continuation, configuration, and platform limits.
- [Shared site branding](features/shared/site-branding/spec.md) — the Vendo browser tab icon and Buyer product video display.
- [Commission and settlement design](design/commission-calculation-and-settlement.md) — fixed Admin 10% merchandise commission, route-based Buyer shipping fee, Rider/Driver accrual, and remaining payout gaps.
- [COD cash and delivery fee distribution](design/cod-cash-and-delivery-fee-distribution.md) — COD-only cash flow from Rider to Vendo and Seller, delivery fee split between Rider, Vendo and Logistics Company, pages per role, and the implementation order. Steps 2 to 7 are written in code with owner migration and verification pending; returns and reversals are not built. See the [29 October pull review](design/2026-10-29-cod-cash-pull-review.md).
- [Web form reload recovery](features/shared/form-draft-recovery/spec.md) — which forms retain unfinished work in the current tab and which inputs must be re-entered.
- [Role workspace redesign](design/2026-10-04-role-workspace-redesign.md) — design and implementation boundaries for the 4 October Buyer, Seller, Logistics, and Admin UI update.
- [Logistics portal design system](design/logistics-design-system.md) — colours, type, interaction rules, and checklist for the Company / Province Hub / Station pages (13 October pass).
- [23 October Logistics pull review](design/2026-10-23-logistics-pull-review.md) — pulled preview pages, province registration change and the owner-approved architecture decision.
- [Logistics Hub restructure backend design](design/logistics-hub-restructure-backend-spec.md) — approved Company / Province Hub / Station structure and operational target. The current implementation and remaining gaps are tracked in the [feature spec](features/logistics/province-network-and-stations/spec.md).
- [7 October frontend pull review](design/2026-10-07-pulled-frontend-review.md) — review of the recent frontend merges, UI-only boundaries, missing backend work, and suggested implementation order.
- [4 October progress](logs/PROGRESS-2026-10-04.md) — team pull review, summary, and role-specific implementation notes.
- [25 September progress](logs/PROGRESS-2026-09-25.md) — messaging, notification and inventory updates.
- [26 September progress](logs/PROGRESS-2026-09-26.md) — policies, cart, notifications, chat, and agent workflow.
- [27 September progress](logs/PROGRESS-2026-09-27.md) — rider registration and separate mobile client integration.
- [28 September progress](logs/PROGRESS-2026-09-28.md) — virtual SH locality route plans and Logistics monitoring/dispatch updates.
- [7 October Admin progress](logs/PROGRESS-2026-10-07.admin-entry.md) and [Buyer progress](logs/PROGRESS-2026-10-07.buyer-entry.md) — detailed notes for the latest role UI updates.
- [7 October backend progress](logs/PROGRESS-2026-10-07.md) — saved items/settings, shop search, Admin complaint/case workflows, and cancellation-rule follow-up.
- [12 October pull progress](logs/PROGRESS-2026-10-12.md) — current team pull review and documentation updates.
- [14 October Logistics decision](logs/PROGRESS-2026-10-14.md) — province network, Main Hub/station hierarchy, and browser-camera scanner direction.
- [23 October pull review](logs/PROGRESS-2026-10-23.md) — Company / Province Hub / Station preview, backend decision gate, and registration province field update.
- [Future plan](future-plan.md) — remaining web and mobile integration work.

## Domains

- [Admin](domains/Admin.md)
- [Buyer](domains/Buyer.md)
- [Seller](domains/Seller.md)
- [Logistics](domains/Logistics.md)
- [Courier](domains/Courier.md)

Feature specifications are under `features/`. Each spec states its current implementation status and points to the relevant code. A `Planned` label means the current code does not implement that capability.

## Status vocabulary

- **Implemented** — routes and server-side behavior exist for the stated scope. This does not claim production readiness or full end-to-end acceptance.
- **Partial** — some server-side behavior exists, but a required actor, transition, validation, or lifecycle step is missing.
- **UI only / placeholder** — a view exists, but its route or action is not backed by the described behavior.
- **Planned** — no working implementation was found in the repository at the review date.

The live source code is authoritative when it differs from a document. Update the relevant spec and `domain-feature-status.md` in the same change that materially changes a feature.
