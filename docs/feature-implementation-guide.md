# Feature Implementation Guide

**Current owner workflow:** Codex implements and documents changes; the project owner writes and runs tests. See [AGENTS.md](../AGENTS.md). The testing steps below describe acceptance coverage for the owner, not a Codex task unless the owner explicitly requests it.

## Use one source of truth

Write the user-visible flow first, then make the route, controller/service, model, migration, view, and tests agree with it. For multi-role workflows, identify the owner of each action and define legal state transitions explicitly. Do not infer permissions from hidden buttons.

## Implementation sequence

1. **Spec:** define actors, entry points, success/failure results, validation, permissions, states, and out-of-scope behavior.
2. **Data:** add or update migration and model relationships/casts; preserve existing rows with a deliberate backfill.
3. **Server:** add named routes and request validation; scope queries to the current actor; use transactions/row locks for competing inventory or order operations.
4. **UI:** preserve approved design, show loading/empty/error/success states, use named routes, and avoid dead `#` actions.
5. **Owner tests:** cover normal flow, invalid transition, unauthenticated/wrong role, cross-tenant resource access, and failure rollback where relevant.
6. **Owner verification:** run focused tests, build frontend assets, render the view, and manually try the flow with local demo data.
7. **Docs:** update the feature spec, domain overview, status matrix, schema/flow docs when applicable, and future-plan dependencies.

## Vendo-specific rules

- Seller routes must require an authenticated, approved, active seller; orders and products must be seller-scoped.
- Buyer routes must use the authenticated buyer as owner and must not trust submitted totals or ownership IDs.
- Admin actions need a distinct admin guard and the active-account/forced-password rules appropriate to the action.
- Logistics center actions must be restricted to records belonging to that center. Courier actions must be restricted to assignments for that courier.
- Checkout reserves/decrements stock today. A later cancellation/refund path must restore stock exactly once and record the actor/reason.
- Keep personal IDs and permits in private storage with controlled access; never include their contents in logs or docs.
- Keep mobile client behavior behind an API boundary; do not make a mobile app depend on Blade endpoints or session HTML.
