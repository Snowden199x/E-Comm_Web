# Vendo Project Documentation

This documentation describes the Laravel web application as it exists in the repository, checked on **26 September 2026**. It distinguishes working code from partial flows, UI placeholders, and future work so that teams do not treat a mock screen as a finished feature.

## Start here

- [Codex working rules](../AGENTS.md) — repository workflow, documentation requirements, role scoping, and the owner's testing preference.
- [Workspace guide](Development-Workspace-&-Task-Planning-guide.md) — local setup, repository boundaries, and contribution rules.
- [Current implementation status](domain-feature-status.md) — the cross-domain source of truth for what is implemented.
- [Feature implementation guide](feature-implementation-guide.md) — how a feature moves from UI through routes, authorization, database, and tests.
- [Architecture](architecture.md) and [schema](schema.md) — current technical structure and data relationships.
- [Order and logistics flow decisions](order-logistics-flow-decisions.md) — current order states and ownership boundaries.
- [Virtual SOC5/SOC6 checkpoints](features/logistics/virtual-soc-checkpoints/spec.md) — proposed inter-hub tracking route; documentation only.
- [Rider scan API](features/courier/scan-api/spec.md) and [Logistics navigation](features/logistics/navigation/spec.md) — current backend contract and center sidebar scope.
- [25 September progress](logs/PROGRESS-2026-09-25.md) — messaging, notification and inventory updates.
- [26 September progress](logs/PROGRESS-2026-09-26.md) — policies, cart, notifications, chat, and agent workflow.
- [Future plan](future-plan.md) — remaining web scope and the planned mobile integration seam.

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
