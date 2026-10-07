# Development Workspace and Task Planning

## Recommended order of work

1. **Stabilize shared foundations:** role and account states, authentication guards, address/profile rules, notifications, and order status vocabulary.
2. **Finish the web buyer journey:** product eligibility, cart/checkout validation, buyer order history, cancellations/refunds, and tracking.
3. **Finish seller operations:** product and inventory lifecycle, seller order handling, pickup handoff, and performance reporting.
4. **Build logistics operations:** courier assignment, pickup confirmation, sorting scans, linehaul/hub routing, and delivery exceptions.
5. **Build the courier client:** authentication, assigned work, scan/status actions, proof of delivery, and offline/network recovery.
6. **Harden cross-cutting operations:** auditability, notifications, access policy, support/disputes, deployment, monitoring, and recovery.
7. **Connect mobile:** publish a versioned API contract and shared authorization/business rules only after web workflows and states are stable.

## Task record template

For each task, record: problem and user role; expected trigger and result; current source behavior; routes and data touched; authorization boundary; acceptance criteria; migration/backfill needs; test cases; UI verification; dependencies; and documentation files to update.

## Prioritization rules

- Resolve security and data-integrity gaps before adding surface-area features.
- Implement state transitions in one authoritative server-side service before adding more clients.
- Prefer end-to-end slices (view + action + persistence + authorization + test) over disconnected UI-only work.
- Keep the web app usable while a separate mobile repository is developed; integrate through an explicit API, not direct database sharing.
