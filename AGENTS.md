# Working Rules for Vendo

## Scope and source of truth

- These rules apply to this repository. Follow the user's current instructions first. Preserve existing work and inspect `git status` before editing; do not discard unrelated changes.
- This is a Laravel 13, PHP 8.3, Blade/Vite marketplace with Admin, Buyer, Seller, Logistics, and planned Courier workflows. Read `docs/README.md`, `docs/domain-feature-status.md`, and the relevant feature spec before changing behavior. Read only the docs needed for the task.
- The application lives in `app/`, `routes/`, `resources/`, and `database/`; it has no `src/api`, Next.js, or React dashboard packages. The current web app uses Laravel guards/sessions. `config/database.php` defaults to SQLite; do not assume PostgreSQL or Sanctum is configured. Follow `docs/architecture.md` before changing stack or integration boundaries.
- Treat live code as evidence of what works. Treat `docs/` as the record of intended behavior and implementation status. If they disagree, inspect the code and update the docs in the same change.
- The course briefs at `/home/Snowden/Downloads/E-Com/ERP-Components-updated.pdf` and `/home/Snowden/Downloads/E-Com/ERP-Flow.pdf` describe the target ERP. They are reference material, not proof that a feature exists. If the files are unavailable, use `docs/order-logistics-flow-decisions.md` and ask for the briefs only when essential.

## Find the right context

| Task | Read |
|---|---|
| What exists and what is planned | `docs/domain-feature-status.md` and the relevant `docs/domains/*.md` |
| Routes, guards, data shape, deployment assumptions | `docs/architecture.md`, `docs/schema.md`, and the affected source files |
| Order states, handoffs, and actor ownership | `docs/order-logistics-flow-decisions.md` |
| Role feature behavior | Matching `docs/features/<role>/<feature>/spec.md`; include each affected spec for cross-role work |
| Prior work and remaining gaps | Latest `docs/logs/PROGRESS-YYYY-MM-DD.md` and `docs/future-plan.md` when relevant |

If a matching spec is missing, use the user's request and existing code to define the behavior in a new spec. Clarify only a decision that cannot be inferred safely; do not invent an implemented state.

## Working loop

1. Identify the actor, route, guard, controller/service, model, migration, view, and relevant spec for the requested behavior. For a small edit, inspect only affected parts.
2. Make the smallest complete change. Keep server validation and authorization with the operation; do not rely on hidden UI controls for access control. Prefer existing named routes, services, status vocabulary, and Blade/CSS conventions.
   Keep work to the requested feature. Do not reformat or refactor unrelated areas. Check existing code before adding a dependency or framework; explain a necessary new dependency in the handoff.
3. Document **every code change in the same work session**. Append a concise entry to `docs/logs/PROGRESS-YYYY-MM-DD.md` using the Asia/Manila date: what changed, why, affected behavior/files, and known gaps. Update the relevant `docs/features/**/spec.md` for behavior changes; update `docs/domain-feature-status.md` when feature scope/status changes. Update architecture, schema, order/logistics decisions, and `docs/README.md` when those pages or links become stale. Never mark a UI placeholder as implemented.
4. The user performs testing. **Do not create or run automated tests, browser tests, or manual app walkthroughs unless the user explicitly asks.** Static review of changed code and `git diff` is fine. State clearly what remains unverified; never claim a test passed when it was not run.
5. In the final handoff, summarize code and docs changed, the practical effect, and any material limitation. Do not overwrite or commit the user's unrelated work.

Stay on the current branch and leave changes uncommitted unless the user asks for a branch or commit. Run project-changing commands from the repository root. Do not modify files or services outside this repository unless the task explicitly requires it.

## Code and schema organization

- Keep controllers focused on HTTP coordination. Use existing middleware/policies for access checks and focused services for workflows shared across roles. Use Form Requests or other focused validation when a controller becomes crowded; do not split a small change mechanically.
- Keep Blade pages and scripts readable. Extract reusable sections only when they have a clear responsibility or are repeated; keep styles in the existing asset structure. Avoid unrelated file moves and compressed one-line source blocks.
- For schema changes, add a new migration rather than editing a migration that may already have run. Preserve existing rows and define a safe backfill when needed. Check the actual database driver and current migration patterns. For new order-like statuses, follow the existing string column plus `Order::STATUSES` validation convention.

## Identity and data isolation

- This codebase currently uses shared tables, roles, seller ownership, and logistics-center relationships. Do not assume a complete tenant framework or separate tenant databases already exist. If introducing a tenant model, define the boundary and migration plan first.
- Derive buyer, seller, center, courier, and admin scope from authenticated identity and verified membership. Request-supplied IDs can select a resource, but must never grant access by themselves. Scope reads, writes, counts, exports, attachments, notifications, and background work to the authorized actor.
- Buyers access only their own carts, orders, support threads, and notifications. Sellers access only their products, inventory, orders, seller conversations, and notifications. Logistics centers access only their own center data and approved/assigned couriers. Couriers may access only assigned pickup/delivery work when courier routes exist. Admin cross-scope actions must be explicit, authorized through the admin guard, and auditable where the app records them.
- Enforce approval and active-account gates server-side: sellers require admin approval; logistics centers require their own approved state; couriers require approval by their linked logistics center before assigned work. Inspect the current middleware and account fields rather than trusting a dashboard redirect.
- Check authorization on every route and every resource lookup, including attachment downloads and JSON refresh endpoints. Keep tenant/user scope in cache keys, queued jobs, and storage paths when these are introduced. Protect private IDs, permits, addresses, and other personal records; do not log secrets or sensitive document contents.

## Commerce and ERP flow

- The target flow is buyer order → seller accepts/prepares → assigned courier picks up → sorting center scans/sorts → center assigns delivery rider → rider delivers → buyer confirms receipt. Follow `docs/order-logistics-flow-decisions.md` for the current status vocabulary and actor ownership. The sorting, assignment, courier, and delivery action routes are still incomplete; do not simulate them with UI-only status changes.
- Courier mobile integration is future work in a separate repository. If asked for courier support here, define the API contract, authentication, approval, and assignment checks before implementation; do not invent a courier web dashboard or assume Sanctum is already installed.
- A checkout may contain selected cart lines from multiple stores; create seller-specific orders and leave unselected lines in the cart. Revalidate selection, ownership, active products, prices, and stock on the server. Calculate totals and fees server-side.
- Use database transactions and row locks for stock, order, and financial changes that can race. Record each stock movement and status transition once. Make retried payment, notification, or fulfillment actions idempotent when implementing them.
- Current checkout supports COD; online payment and full shipping quotation are future work. Commission comes from the app's category settings. The ERP brief mentions 10%, but do not hard-code that value or silently change configured rates; record any requirement conflict in docs.
- Send notifications only to intended recipients. Buyer purchases notify the seller, not the admin order feed. Admin operational notifications cover new registrations, submitted reports/complaints, and support messages. Keep message participants and deletion rights role-scoped; current support conversation deletion is admin-only.

## UI and safe changes

- Keep the existing Vendo visual language. Use the `uncodixfy` skill for frontend UI when available. Make controls usable by keyboard and on small screens; include honest empty/error/loading states where the flow needs them.
- Validate uploads by type and size, authorize downloads, and keep verification documents private. Never place credentials in source control. Avoid destructive database commands, production data changes, or broad file cleanup unless explicitly requested.

## Research references

- [OpenAI guidance on concise agent instructions](https://developers.openai.com/blog/rethinking-skills-and-prompts-for-gpt-6-astra)
- [Laravel authorization](https://laravel.com/framework/docs/13.x/authorization) and [database transactions/locks](https://laravel.com/framework/docs/13.x/queries)
- [OWASP multi-tenant security guidance](https://cheatsheetseries.owasp.org/cheatsheets/Multi_Tenant_Security_Cheat_Sheet.html)
