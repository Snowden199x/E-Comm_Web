# Settlement Ledger, Payouts and Return/Refund Policy

**Status:** Proposal. No code for this document exists yet. Nothing here is implemented; the "Current behavior" section describes code as it stands on 11 November 2026.
**Reviewed:** 11 November 2026 (static read of `app/`, `database/migrations/`, `docs/`; no tests or walkthroughs run).
**Purpose:** give the owner one page to approve or change before any ledger or payout code is written. Every item is a numbered decision (D1–D16) with the options, the recommendation, and the reason.
**Related:** [COD cash and delivery fee distribution](cod-cash-and-delivery-fee-distribution.md) (treated as superseded where this document disagrees), [commission and settlement design](commission-calculation-and-settlement.md), [commission and settlement spec](../features/shared/commission-settlement/spec.md), [Seller earnings](../features/seller/earnings/spec.md), [province and company cash](../features/logistics/province-company-cash/spec.md), [order and logistics decisions](../order-logistics-flow-decisions.md).

---

## 1. What the code does today

Money is COD only. Four separate mechanisms already exist, and none of them is a ledger.

| Mechanism | Where | What it stores | What it does not do |
|---|---|---|---|
| Shipping accrual | `order_shipping_settlements`, `order_shipping_earning_lines` (`ShippingEarningsSettlementService`) | Per delivered order: fee, hub count, rider total, Vendo delivery commission, Logistics remainder, `accrued`/`review` status, one line per credited rider/driver | No payout state, no reversal, no allocation across companies, `settled_at` is written at accrual time |
| Cash custody | `order_cod_collections`, `cash_remittances`, `cash_remittance_items`, `orders.money_status` | Which parcel's cash is with which actor, batch by batch, up to `credited_to_seller` | Mixes "where the cash is" with "the seller has been paid"; no refund path back down the chain |
| Seller earnings | `SellerEarningsController` + `SellerEarningsCalculator` | Recomputed on read from `orders.total_amount`, `voucher_discount`, `seller_commission_rate`, `money_status` | Nothing is stored: no earning row, no paid state, no reversal, no balance |
| Rider/Driver earnings | `RiderEarningsController` | Sum of `order_shipping_earning_lines` for that user | No payout, no paid state, no company-side view of what it has already disbursed |

Consequences of having no ledger:

- A seller's "Credited" figure is a **filter over order rows**, not an amount owed. If an order is later returned or reversed, nothing in the database can express that.
- A rider's accrued total is a live `SUM`. It changes when an unrelated row changes; it is not a record of anything.
- Cash shortages, overpayments, refunds and offsets have no home, so they can only be handled by editing `money_status` or by notes in `cash_remittances`.
- No page can answer "how much do we owe, to whom, and what has already been paid".
- **Nothing in the application ever sets `orders.status = 'returned'`.** The status exists in `Order::STATUSES`, reports count it (`Admin\DashboardController`, `Admin\ReportController`), and several queries exclude it, but no buyer, seller, rider, facility or admin action writes it. Returns are absent, not partial.

### 1.1 Defects found while mapping the flow

These are independent of the policy decisions. Items 1 and 2 were fixed on 11 November 2026; the rest still need fixing before or with Phase 1 (section 6).

1. **`CashRemittanceService` read an attribute that does not exist.** `$order->seller_net_earnings` was not defined anywhere in `app/`, so `sum()` returned `0`. Every "Earnings credited" notification announced **₱0.00** regardless of the real amount. **Fixed 11 November 2026:** the notice moved to the delivered scan (`ShippingEarningsSettlementService`) and is computed with `SellerEarningsCalculator` (items minus voucher minus the order's saved commission); confirming the cash later no longer sends a second one.
2. **Rider accruals vanished when the buyer confirmed receipt.** `RiderEarningsController` filtered `orders.status = 'delivered'`, but buyer receipt confirmation sets `completed` (`Buyer\OrderController:59`, `Api\Buyer\BuyerOrderController:83`). **Fixed 11 November 2026:** the API now filters `Order::SALES_STATUSES`, so an accrual stays visible after the buyer confirms.
3. **`order_shipping_settlements.settled_at` is not a settlement date.** It is written when the accrual is created, so the column name claims a payout that never happened.
4. **`money_status = 'credited_to_seller'` conflates two facts.** `CashRemittanceService::receive()` sets it when Vendo confirms a Company's batch. That is the moment Vendo holds the cash, not the moment the seller was paid. A ledger separates custody from credit-from-Vendo.
5. **Cash discrepancy resolution is all-or-nothing.** `resolve()` moves every held order to received and credits the sellers in full; a partial shortage cannot be recorded, so the shortage is silently absorbed.

---

## 2. Ledger model (D1–D5)

### D1 — Append-only journal instead of balance columns

| Option | Description |
|---|---|
| A | Keep computing everything from order rows (status quo). |
| **B (recommended)** | Add an append-only `settlement_entries` journal. One row per money fact. Balances are `SUM(amount_cents)` per party. Corrections are new negative rows, never edits. |
| C | Add mutable balance columns (`users.earnings_balance`) updated in place. |

**Recommendation: B.** It survives reversals and returns, gives an audit trail for free, and lets a payout reference exact entries. Option C loses the "why" and races under concurrent updates.

### D2 — One journal for all parties, or one per party

**Recommendation:** one journal with a `party_type` (`seller`, `rider`, `transport_driver`, `logistics_company`, `vendo`, `buyer`) and `party_id` (null for Vendo). One set of rules, one rounding rule, one place to audit. Separate per-party tables would duplicate every reversal path.

### D3 — Sign convention and rounding

**Recommendation:** `amount_cents` is a **signed integer**: positive = value owed to the party, negative = value taken from them. All arithmetic stays in centavos with the existing half-up helper (`DeliveryFeeDistributionCalculator::percentOf`); nothing is stored as a float. Balances may go negative (a seller owing money after a return is real); payouts only ever pay positive available balances.

### D4 — Custody stays where it is

`orders.money_status`, `cash_remittances` and `order_cod_collections` keep their current meaning: **physical location of cash**. The ledger records entitlements and payouts. Both are needed; they answer different questions. Do not merge them, and do not add ledger state into `Order::STATUSES`.

### D5 — Idempotency and immutability

**Recommendation:** every entry carries a unique `idempotency_key` built from its cause (`ship:{settlement_id}:rider`, `sale:{order_id}:seller`, `comm:{order_id}:seller_commission`, `refund:{return_case_id}`, `payout:{batch_id}:{entry_id}`). Inserts use `insertOrIgnore` inside the same transaction as the business change, exactly like `order_shipping_earning_lines` does now. No `UPDATE` or `DELETE` on entries; a mistaken entry is corrected by a compensating entry that references it in `reverses_entry_id`.

### Proposed data shape (additive migrations)

| Table | Columns (abridged) | Purpose |
|---|---|---|
| `settlement_entries` | `id`, `occurred_at`, `order_id?`, `remittance_id?`, `return_case_id?`, `party_type`, `party_id?`, `kind`, `amount_cents`, `available_at?`, `idempotency_key` unique, `reverses_entry_id?`, `note?`, `meta` json, timestamps | The journal |
| `payout_batches` | `id`, `party_type`, `party_id`, `method` (`cash`, `bank`, `ewallet`, `offset`), `reference?`, `status` (`draft`, `approved`, `paid`, `void`), `period_from`, `period_to`, `total_cents`, `created_by`, `approved_by?`, `paid_at?`, `note?` | One disbursement run for one party |
| `payout_batch_items` | `id`, `batch_id`, `entry_id` unique, `amount_cents` | Which entries a batch pays |
| `return_cases` | `id`, `order_id`, `requested_by`, `reason_key`, `details?`, `status`, `decided_by?`, `decided_at?`, `fault` (`seller`, `buyer`, `vendo`, `undetermined`), `refund_cents`, `refund_method?`, `refund_reference?`, `return_leg_fee_cents`, `returned_at?`, `received_at?`, `refunded_at?`, timestamps | The return/refund workflow |

`kind` values for Phase 1: `seller_net_earning`, `seller_commission`, `rider_parcel_pay`, `vendo_delivery_commission`, `logistics_remainder`, `seller_payout`, `rider_payout`, `company_payout`, `cash_shortage`, `adjustment`. Later: `refund_obligation`, `refund_paid`, `return_leg_charge`, `return_leg_pay`, `reverse_*`.

Every entry for a delivered order is written in **one transaction** at the moment the existing code already invests the money fact — the delivered scan, which since 11 November 2026 also credits the Seller (the cash chain no longer gates earnings) — so the ledger can never disagree with `order_shipping_settlements` or `money_status`.

---

## 3. Payout policy (D6–D10)

Today there is no payout concept at all: "Credited" means "Vendo confirmed the Company's cash", and nothing records that anyone was actually paid. Since Vendo has no payment provider and the owner runs the operation, the first step must be honest and small.

### D6 — How a payout happens

| Option | Description |
|---|---|
| A | Nothing (status quo). Earnings stay a display figure. |
| **B (recommended)** | **Recorded manual payout.** Admin/Company creates a batch, reviews the exact entries, approves, pays outside the system (cash at the hub, bank/e-wallet transfer), then records method + reference + date. The batch marks those entries paid once. |
| C | Provider integration (GCash/bank payout API) now. |

**Recommendation: B.** It makes "paid" a fact without KYC, provider contracts, or webhooks, and it is exactly the data a provider integration needs later. C is a separate later decision and needs its own research.

### D7 — Who pays whom

| Party | Paid by | Notes |
|---|---|---|
| Seller | Vendo | Vendo already holds the cash after `company_to_vendo` |
| Rider / Transport Driver | Logistics Company | The Company keeps `rider_total` out of the COD cash it collects, so it already holds this money |
| Logistics Company | Nobody | Its share is what it keeps; Vendo only collects the remainder |
| Buyer refunds | Vendo (or netted against the Company's remittance, D13) | |

**Recommendation as tabled.** It matches the existing cash split and keeps Vendo out of rider employment.

### D8 — Eligibility and holdback

**Recommendation:**
- **Seller:** an entry becomes available at `max(cash confirmed by Vendo, delivered_at + 7 days)`. The 7 days is the return window (D11), so money that can still be clawed back is not paid out.
- **Rider/Driver:** available at `delivered_at + 3 days`. They performed the work; a return should not usually take pay back.
- Both are stored as `available_at` on the entry so eligibility is a query, not a recomputation, and changing the policy later does not rewrite history.

### D9 — Schedule and minimums

**Recommendation:** Admin/Company triggers a batch on demand ("pay everyone eligible now") with a suggested weekly run; no cron in the first version. Minimum per party per batch: **₱500 for Sellers, ₱100 for Riders/Drivers**; smaller balances roll into the next batch (except on account closure, where a final batch is allowed). Amounts below the minimum stay visible as "On hold".

### D10 — Corrections after a payout

**Recommendation:** never edit a paid entry. A later reversal creates a negative entry that offsets future availability, so a party with a paid-out return is recovered from their next payout, never invoiced. A payout batch can be `void` before `paid`; after `paid` it can only be corrected by entries.

---

## 4. Return and refund policy (D11–D16)

COD makes refunds unusual: the money is cash that already travelled up the chain, and Vendo cannot "reverse a card". Two return moments must be distinguished.

- **Refusal at the doorstep** (buyer rejects, item visibly damaged, no cash collected): no money moves, `money_status` stays `void`/`not_collected`, no ledger entries, no rider pay. This already works as designed.
- **Return after a paid delivery**: the buyer has paid, the seller may already be credited, and someone must fund the refund. That is what D11–D16 define.

### D11 — Window and who decides

**Recommendation:**
- Buyer may open a return within **7 calendar days of `delivered_at`**, from the order page. The reason is chosen from a fixed list with optional details and photos (private, order-scoped).
- **Seller decides within 48 hours** (approve or reject with a reason). Silence for 48 hours moves the case to the **Admin** queue; it does not auto-approve. Admin may decide any case and must give a reason.
- Refunds above **₱2,000** always require Admin approval even if the seller already agreed.
- After the window, the buyer can still file a complaint through the existing Complaints flow; Admin decides, and only Admin can create a return case late.

### D12 — Who funds a return (fault-based)

Using the document's running example: ₱500 items, ₱100 delivery fee, ₱50 seller commission, rider ₱10, Vendo delivery commission ₱18, Company ₱72.

| Fault | Buyer receives | Seller | Vendo | Rider / Company |
|---|---|---|---|---|
| **Seller fault** (defective, wrong, not as described, missing) | 100% of `cod_amount` (₱600) | Loses net earnings (₱450) and pays the return leg; must mark the item received before the refund is paid | Gives up the item commission (₱50) and its delivery commission (₱18) to fund the refund | Keep their shares; the delivery happened |
| **Buyer change of mind** | Items − voucher (₱500); delivery fee is not refunded | Loses net earnings only if the seller agrees; otherwise the return is rejected | Keeps its delivery commission; funds the return leg if it waives it as goodwill | Rider is paid `return_leg_fee` in cash by the buyer |
| **Vendo/Logistics fault** (lost, damaged in transit, late beyond policy) | 100% of `cod_amount` | Keeps net earnings (paid as if delivered) | Absorbs the whole refund | Rider keeps pay; Company share is charged back to the Company |

**Recommendation as tabled.** It always balances: reversed seller earnings + reversed Vendo commissions + cash Vendo holds equal the refund. No party is asked for money it was never given.

### D13 — How the buyer gets the cash back

| Option | Description |
|---|---|
| A | Rider brings cash back to the buyer. |
| B | Buyer collects cash at the Station Hub. |
| C | Vendo sends a bank/e-wallet transfer. |
| **D (recommended)** | Record the obligation, then settle it by **any** of the above, captured as a payout batch item with method + reference. If Vendo has not yet received the Company's remittance for that order, **net the refund against the Company's `vendoDue`** instead, so no cash has to move twice. |

**Recommendation: D**, because the app should not pretend to move money it cannot move, and the netting rule avoids Vendo fronting cash for money it has not collected yet. The buyer sees "Refund approved — ₱600 to be released" and then "Refund paid" with the reference; never "Refunded" before it is.

### D14 — Return shipment and restocking

**Recommendation:**
- Seller-fault returns are **free for the buyer**; the return leg is charged to the seller at the Company's `return_leg_fee` (new setting, default **₱45**) and paid to the rider who performs it.
- Change-of-mind returns are **paid by the buyer in cash to the rider** on pickup (recorded as a collection, not part of the original order's cash).
- Stock is restored **only when the seller marks the item received and serviceable**, through the existing `InventoryMovement` path with a `return_restock` reason, idempotent per return case. Unsellable items get a `return_writeoff` movement instead.
- The refund is released only after `received_at` is set, except an Admin `refund_without_return` for amounts ≤ **₱200** (cheaper to absorb than to ship back), which must be logged.

### D15 — Scope: whole order first

**Recommendation:** Phase 4 handles **whole seller-orders only** (one seller-order returned in full). Per-line and partial-quantity returns need per-line money allocation, restock per line, and a UI for partial selection; that is a separate later slice and should not be faked now.

### D16 — Disputes after a refund

**Recommendation:** a refunded order is terminal for money purposes. Re-opening a case creates a new case linked to the old one; the ledger is never rewritten. The seller may contest a seller-fault decision within 7 days; Admin decides and records the outcome in the case, and any money correction is a new entry.

---

## 5. Cross-company routes (part of the ledger, needs one more decision)

The Logistics remainder on a route that touches two companies is currently reported as **unallocated**. The ledger can only post it once ownership is decided.

| Option | Description |
|---|---|
| A | The company that collected the cash keeps the whole remainder. |
| B | Split by the number of facility stops each company handled, rounding remainder to the delivery company. |
| C | Split by the shipping fee share each company's stops would have quoted. |

**Recommendation: B for the first version**, posted as one `logistics_remainder` entry per company with deterministic rounding, because it is explainable in one sentence to a company and needs no tariff data. A and C stay open. This can be deferred without blocking Phases 1–3, because single-company routes are unaffected.

---

## 6. Implementation phases after approval

| Phase | Content | Acceptance |
|---|---|---|
| **0** | Fix the five defects in 1.1. No schema change. | Rider totals survive buyer confirmation; the credit notification shows the real amount; no page claims a payout exists. |
| **1** | `settlement_entries` + writes from the delivered scan (including the Seller credit, now due at delivery) and from Company→Vendo confirmation for custody. Admin "Settlement" read-only page: balances per party, entries per order, a `review` queue. One-time backfill of existing accruals and credited sellers. | Every delivered order with a settlement has matching entries; `SUM` per order equals the split; replaying the same scan or confirmation duplicates nothing; pre-ledger orders are labelled, not invented. |
| **2** | Seller payout batches (`payout_batches`, `payout_batch_items`), holdback/availability, Admin approve → paid, Seller earnings page shows Pending / Available / Paid / On hold from the ledger. | Cannot pay an unavailable or already-paid entry; a paid batch cannot be paid twice; reversing availability is impossible after payment. |
| **3** | Rider/Driver payout batches owned by the Logistics Company, and the Rider API reporting available / paid instead of a live sum. | Rider pay can only be paid by their own company; totals match the ledger; mobile contract documented before the app changes. |
| **4** | Return cases: buyer request, seller/Admin decision, return leg, receive, refund obligation, reversals, restock, refund settlement. | A full seller-fault return zeroes the order's seller entries and pays the buyer exactly `cod_amount`; the Company and rider entries are untouched; a refund cannot be marked paid before `received_at` (except the ₱200 rule); every step is idempotent. |
| **5** | Cross-company allocation (section 5), settlement CSV/PDF export, shortage handling as ledger entries instead of full resolution. | Company reports add up to the shipping fees collected; no remainder is double-posted. |

Each phase follows `AGENTS.md`: new migrations only, `docs/logs/PROGRESS-YYYY-MM-DD.md` entry, matching spec updates, and `docs/schema.md` / `domain-feature-status.md` when those become stale. The owner verifies in the browser; no automated tests are added unless asked.

---

## 7. Explicitly out of scope

Bank/e-wallet disbursement APIs, KYC, BIR receipts/invoicing, tax withholding, multi-currency, weight/volume pricing, buyer wallet or store credit, card/online payment, chargebacks, per-line partial returns, and employee payroll. Do not display estimates as payouts, and do not call a report total "money owed" unless it comes from the ledger.

---

## 8. What the owner needs to confirm

Approve or change each item; a one-line "approve all except Dn" is enough.

| # | Decision | Recommendation |
|---|---|---|
| D1 | Append-only journal | Yes |
| D2 | One journal, all parties | Yes |
| D3 | Signed centavos, half-up | Yes |
| D4 | Keep `money_status` for custody only | Yes |
| D5 | Idempotency keys, no edits | Yes |
| D6 | Manual recorded payouts first | Yes |
| D7 | Seller paid by Vendo, rider by Company | Yes |
| D8 | Holdback: seller 7 days, rider 3 days | Yes |
| D9 | On-demand batches; ₱500 / ₱100 minimums | Yes |
| D10 | Corrections only by new entries | Yes |
| D11 | 7-day window; seller 48h; Admin above ₱2,000 | Yes |
| D12 | Fault-based funding table | Yes |
| D13 | Recorded refund settlement, net against `vendoDue` when possible | Yes |
| D14 | Return leg ₱45 default; restock on receive; ₱200 no-return rule | Yes |
| D15 | Whole-order returns first | Yes |
| D16 | Terminal refunds, new case to reopen | Yes |
| D17 | Cross-company remainder: split by stop count | Yes (or keep deferred) |

Two numbers are pure business calls and can be changed after approval without touching the design: the 7-day return window/holdback and the ₱500 / ₱100 payout minimums.

---

## 9. Source evidence

- `app/Services/ShippingEarningsSettlementService.php`, `app/Services/DeliveryFeeDistributionCalculator.php` — accrual and split.
- `app/Services/CashRemittanceService.php` — custody chain, `credited_to_seller`, the undefined `seller_net_earnings` at line 99.
- `app/Models/Finance/CashRemittance.php`, `app/Models/Finance/OrderCodCollection.php`, `app/Models/Ecommerce/Order.php` (`MONEY_STATUSES`) — current money states.
- `app/Http/Controllers/Seller/EarningsController.php`, `app/Services/SellerEarningsCalculator.php` — earnings recomputed on read.
- `app/Http/Controllers/Api/RiderEarningsController.php` — accrual API over `Order::SALES_STATUSES`.
- `app/Http/Controllers/Buyer/OrderController.php:59`, `app/Http/Controllers/Api/Buyer/BuyerOrderController.php:83` — receipt confirmation writes `completed`.
- `database/migrations/2026_10_23_220000_add_shipping_quotes_and_earnings.php`, `2026_10_28_100000_add_cod_and_delivery_fee_settings.php`, `2026_10_28_110000_create_cod_cash_remittance_tables.php` — existing tables and rate snapshots.
