# Vendo Project Documentation

This documentation describes the Laravel web application as it exists in the repository, checked on **7 October 2026**. It distinguishes working code from partial flows, UI placeholders, and future work so that teams do not treat a mock screen as a finished feature.

## Start here

- [Codex working rules](../AGENTS.md) — repository workflow, documentation requirements, role scoping, and the owner's testing preference.
- [Workspace guide](Development-Workspace-&-Task-Planning-guide.md) — local setup, repository boundaries, and contribution rules.
- [Current implementation status](domain-feature-status.md) — the cross-domain source of truth for what is implemented.
- [Feature implementation guide](feature-implementation-guide.md) — how a feature moves from UI through routes, authorization, database, and tests.
- [Architecture](architecture.md) and [schema](schema.md) — current technical structure and data relationships.
- [Security overview](security.md) — code-based web/API controls, deployment configuration, and known security gaps; not a penetration-test report.
- [Terms and Conditions](legal/terms-and-conditions.md), [Privacy Policy](legal/privacy-policy.md), and [policy release review](legal/release-review.md) — source for the public web pages and remaining consent/privacy follow-ups.
- [Order and logistics flow decisions](order-logistics-flow-decisions.md) — current order states and ownership boundaries.
- [Virtual SH locality routing](features/logistics/subhub-routing/spec.md), [Main Hub route plans](features/logistics/hub-to-hub-routing/spec.md), and [truck/linehaul dispatch](features/logistics/company-truck-linehaul-dispatch/spec.md) — route selection and remaining scanner/handoff gaps. Courier, rider, truck, and scanner interfaces are mobile-only.
- [Rider registration API](features/courier/registration-api/spec.md), [rider scan API](features/courier/scan-api/spec.md), [Logistics navigation](features/logistics/navigation/spec.md), and [Logistics account management](features/logistics/account-management/spec.md) — current backend contracts and center account/sidebar scope.
- [Google sign-in](features/shared/google-auth/spec.md) — verified web and Rider token exchange, registration continuation, configuration, and platform limits.
- [Shared site branding](features/shared/site-branding/spec.md) — the Vendo browser tab icon and Buyer product video display.
- [Web form reload recovery](features/shared/form-draft-recovery/spec.md) — which forms retain unfinished work in the current tab and which inputs must be re-entered.
- [Role workspace redesign](design/2026-10-04-role-workspace-redesign.md) — design and implementation boundaries for the 4 October Buyer, Seller, Logistics, and Admin UI update.
- [7 October frontend pull review](design/2026-10-07-pulled-frontend-review.md) — review of the recent frontend merges, UI-only boundaries, missing backend work, and suggested implementation order.
- [4 October progress](logs/PROGRESS-2026-10-04.md) — team pull review, summary, and role-specific implementation notes.
- [25 September progress](logs/PROGRESS-2026-09-25.md) — messaging, notification and inventory updates.
- [26 September progress](logs/PROGRESS-2026-09-26.md) — policies, cart, notifications, chat, and agent workflow.
- [27 September progress](logs/PROGRESS-2026-09-27.md) — rider registration and separate mobile client integration.
- [28 September progress](logs/PROGRESS-2026-09-28.md) — virtual SH locality route plans and Logistics monitoring/dispatch updates.
- [7 October Admin progress](logs/PROGRESS-2026-10-07.admin-entry.md) and [Buyer progress](logs/PROGRESS-2026-10-07.buyer-entry.md) — detailed notes for the latest role UI updates.
- [7 October backend progress](logs/PROGRESS-2026-10-07.md) — saved items/settings, shop search, Admin complaint/case workflows, and cancellation-rule follow-up.
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
