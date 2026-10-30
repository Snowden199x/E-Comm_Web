# Vendo COD Cash Flow and Delivery Fee Distribution

**Status:** Proposed design, partly implemented. Steps 2 to 7 of the implementation order (database foundation, per-parcel settlement and rate snapshots, cash collected at delivery on the web API, Station Hub, Province Hub, Company and Vendo Admin cash pages, Rider Payment settings, Seller earnings, Buyer COD notices, and per-order seller rates in the Admin Commission and Reports pages) are written in code with owner migration and verification pending; see the [29 October pull review](2026-10-29-cod-cash-pull-review.md). Not implemented: step 8 (returns and failed-delivery reversals), the mobile app change (`E-Comm_Mobile`), and the open business decisions in section 9. Section 6 describes the code as it was before step 2.
**Reviewed:** 28 October 2026, against the code in `E-Comm_Web.zip` (static read only; no tests or walkthroughs were run).
**Related:** [commission-calculation-and-settlement.md](commission-calculation-and-settlement.md), [logistics-hub-restructure-backend-spec.md](logistics-hub-restructure-backend-spec.md), `docs/order-logistics-flow-decisions.md`

Vendo accepts **COD only**. That means the buyer's cash is the only source of money in the system, and it has to travel back from the doorstep to the seller, the rider, the logistics company and Vendo. This document defines that flow, how the delivery fee is divided, the pages each role needs, and what in the current code has to change.

There are **two separate money flows**. Keep them separate in code and in the UI.

1. **Delivery fee distribution** — how the delivery fee the buyer pays is split between the Rider, Vendo and the Logistics Company.
2. **COD cash remittance** — how the physical cash collected by the Rider moves up the hub chain and is finally settled with the Seller and Vendo.

---

## 1. Decisions already made

| Topic | Decision |
|---|---|
| Payment mode | COD only. |
| Who pays the delivery fee | The **Buyer**. It is added to the COD amount. |
| Rider pay | A fixed amount **per delivered parcel**, set by the Logistics Company (example: ₱10). Editable. Paid out by the Logistics Company. |
| Pickup work | Pickup is the Rider's **salary** from the Logistics Company. It is outside the fee split. |
| Vendo commission on delivery fee | A **global percentage** set by the Vendo Admin, applied to the balance **after** the rider's pay is deducted (example: 20%). |
| Logistics Company share | Whatever remains after the rider's pay and Vendo's commission. |
| Vendo commission on items | Vendo takes a percentage of the **item price** from the Seller (currently 10%; "10% or something", so it must be editable). |
| Who pays the Seller | **Vendo**, not the Logistics Company. There is no bank or e-wallet and no real money moves: the Seller's net earnings are **credited automatically** and shown on the Seller earnings page. |
| Rates and history | Rates are saved on each order so later rate changes never alter past records. |
| Failed or returned parcel | No cash is collected, so **no fee split is recorded** and the Rider earns nothing for it. |
| Rider cash handling | The Rider remits **all** cash collected. Their earnings build up separately in a My Earnings view and are paid by the Logistics Company with their salary. |

## 2. Worked example

One parcel: item price ₱500, delivery fee ₱100, rider pay ₱10, Vendo delivery commission 20%, Vendo seller commission 10%.

**What the buyer pays on delivery:** ₱500 + ₱100 = **₱600**.

### 2.1 Delivery fee split

| Step | Amount |
|---|---|
| Delivery fee paid by Buyer | ₱100.00 |
| 1. Rider pay (fixed per delivered parcel, set by Logistics Company) | ₱10.00 |
| 2. Remaining balance (₱100 − ₱10) | ₱90.00 |
| 3. Vendo commission (20% of ₱90, set by Vendo Admin) | ₱18.00 |
| 4. Logistics Company earnings (₱90 − ₱18) | ₱72.00 |
| Total distributed | ₱100.00 |

The 20% is only an example and can be changed by the Vendo Admin.

### 2.2 Item price split

| Step | Amount |
|---|---|
| Item price | ₱500.00 |
| Vendo seller commission (10% of item price) | ₱50.00 |
| Net earnings credited to Seller | ₱450.00 |

### 2.3 Where the ₱600 ends up

| Party | Receives | How |
|---|---|---|
| Seller | ₱450 | Credited automatically on the Seller earnings page |
| Rider | ₱10 | Credited to My Earnings, paid by the Logistics Company with salary |
| Logistics Company | ₱72 | Kept from the cash it received |
| Vendo | ₱68 | ₱18 delivery commission + ₱50 seller commission |
| **Total** | **₱600** | |

The Logistics Company holds the full ₱600 once the cash reaches it. It keeps ₱72, holds ₱10 for the Rider, and **remits ₱518 to Vendo** (the ₱500 item price plus Vendo's ₱18 delivery commission). Vendo then pays the Seller ₱450 and keeps ₱68.

## 3. Cash and parcel journey

1. **Checkout.** The Buyer sees "Pay ₱600 on delivery". The order is created and the rates in force are saved on it (see section 5).
2. **Parcel moves** through the hubs using the existing hub-match routing. The route is unchanged by this design.
3. **Delivery.** The Rider collects ₱600 in cash and the app records the amount collected. The parcel is marked Delivered. The money status becomes **Collected by Rider** and the fee split is calculated and recorded in the same transaction.
4. **Rider remits to the Station Hub.** The Rider hands over all cash collected. The Station Hub counts it, then confirms it or flags a shortage or overage.
5. **Station Hub remits to the Province Hub** in batches. The Province Hub verifies it. A parcel delivered inside the same province still passes through its Province Hub for cash purposes.
6. **Province Hub remits to the Logistics Company.**
7. **Logistics Company remits to Vendo** the item price plus Vendo's delivery commission, keeping its own share and the Rider's pay.
8. **Vendo credits the Seller automatically.** When Vendo confirms it received the remittance, the Seller's earnings (item price minus the seller commission) appear as credited on the Seller earnings page. No payout approval or bank details are involved.

### 3.1 Failed, cancelled and returned parcels

- No cash is collected, so there is nothing to remit and no fee split is recorded.
- The Rider earns nothing for a failed delivery.
- Return shipping cost and any later return refund workflow are **not decided** (see section 9).

### 3.2 Money status per order

Run a second status chain beside the parcel status chain. Do not add these values to `Order::STATUSES`; store them in their own column.

`not_collected` → `collected_by_rider` → `remitted_to_station` → `remitted_to_province` → `received_by_company` → `credited_to_seller`

Two side states: `discrepancy` (a shortage or overage is open at any hop) and `void` (the parcel failed or was returned, so no cash was ever collected).

## 4. Rules and calculations

- **Integer centavos.** Calculate in integer centavos (the current settlement service already does) and store as `decimal(10,2)` like the existing money columns.
- **Rider pay** is read from the Logistics Company's setting and must be between ₱0 and the delivery fee.
- **Vendo delivery commission** is a percentage from 0 to 100, applied to `delivery fee − rider pay`. Round half up to the centavo.
- **Logistics share is always the remainder** (`delivery fee − rider pay − Vendo commission`) so the four amounts always add up to the delivery fee exactly.
- **Seller commission** is a percentage of the item price. It does not apply to the delivery fee.
- **Voucher discounts.** Buyer payable today is `items − voucher + shipping`, so the COD amount is that figure. Whether the seller commission is taken on the gross or the discounted item price is **not decided** (see section 9).
- **Settings are snapshotted per order** when the order is created, inside the existing order-creation transaction. Changing a rate later affects new orders only.
- **Idempotency.** Collection, settlement and remittance actions must be safe to retry. Use transactions and row locks, as the existing settlement service does.
- **Same hub-match routing, same facilities.** Cash moves along the same Station → Province → Company hierarchy as parcels.

## 5. Proposed data shape

> The migrations below now exist. The shape is additive: add new migrations, and do not edit migrations that may already have run.

| Table or column | Purpose |
|---|---|
| `delivery_fee_settings` (or `platform_settings` keys) | Global Vendo delivery commission % and seller commission %, with who changed them and when. |
| `logistics_company_rider_pay` (or a column on `logistics_companies`) | Fixed rider pay per delivered parcel, per Logistics Company. |
| `orders.cod_amount` | Exact cash the Rider must collect, frozen at checkout. |
| `orders.money_status` | The money status chain from section 3.2. |
| `orders.rider_pay_rate`, `orders.vendo_delivery_commission_rate`, `orders.seller_commission_rate` | Rate snapshots saved at order creation. |
| `order_cod_collections` | One row per delivered order: amount collected, Rider, Station Hub, collected-at, scan key for idempotency. |
| `cash_remittances` | One row per handoff: from (Rider or facility), to (facility, company or Vendo), batch reference, expected amount, received amount, status, verified by, discrepancy note. |
| `cash_remittance_items` | The orders included in each remittance batch. |
| *(no seller payout table)* | Seller earnings are calculated from the order: item total, the seller commission rate snapshotted on the order, and the money status. Nothing extra is stored. |
| Extend `order_shipping_settlements` | Add the Vendo delivery commission and change the Rider line to one per delivered parcel (see section 6). |

## 6. Current code and what conflicts with this design

Findings from `E-Comm_Web.zip` (reviewed 28 October 2026).

**Already in code**

- Checkout accepts only `payment_mode = cod` and creates one order per seller.
- `CheckoutShippingQuoteService` quotes ₱45 for up to two facility stops plus ₱10 per additional stop. The fee, hub count and route snapshot are stored on the order.
- `Order` buyer payable is `total_amount − voucher_discount + shipping_fee`.
- `ShippingEarningsSettlementService` runs when a parcel is scanned delivered and writes `order_shipping_settlements` and `order_shipping_earning_lines`.
- `RiderEarningsController` exposes accrued Rider and Transport Driver earnings.
- Admin commission reports apply a fixed 10% to item price for delivered or completed orders.
- Company Reports show a shipping settlement summary.

**Conflicts to resolve**

| Area | Current code | This design |
|---|---|---|
| Rider pay | ₱10 **per route stop**, to the Rider or Driver responsible for each stop (`RIDER_SHARE_CENTS_PER_HUB`, hardcoded). | One fixed amount **per delivered parcel**, to the delivery Rider only, set by the Logistics Company. Pickup riders and Transport Drivers are salaried. |
| Vendo share of delivery fee | None. "Admin receives no shipping share." | Vendo-set percentage of the balance after rider pay. |
| Rates | Hardcoded: ₱45, ₱10, 10% (`CommissionSetting::FIXED_RATE`). The Admin rate editor was removed. | Editable settings for the Vendo Admin and each Logistics Company. |
| Rate snapshots | Orders do not store the commission rate; reports recalculate with the current rate. | Rates saved per order. |
| Delivery proof | Records recipient name and photo only. | Also records the cash amount collected. |
| `AGENTS.md` | Says not to hard-code the 10% commission. | Consistent: move the rate into settings. |
| Cross-company routes | The Logistics remainder is reported as "unallocated". | Still open (section 9). |

**Missing entirely**

- Any record of COD cash collected, cash on hand per Rider, or remittance between hubs.
- Any money page in the Company, Province or Station navigation (`config/logistics_nav.php`).
- A Seller earnings page. Net Seller earnings accounting is documented as not implemented.
- Vendo paying Sellers, and any reversal for returns or failed deliveries.

## 7. Pages by role

Rider and Transport Driver interfaces are **mobile-only** (see `AGENTS.md`). Do not build Rider web dashboards. Rider cash and earnings live in the mobile app in the separate `E-Comm_Mobile` repository. The Station Hub web pages below cover riders' cash.

### 7.1 Rider (mobile app, not this repo)

- Show the exact COD amount to collect on each delivery.
- Require the collected amount when completing delivery.
- Cash on hand and remittance history.
- My Earnings: the per-parcel pay for each delivered parcel.

### 7.2 Station Hub (web, `lgp.station.*`)

Add to the **Business** group in `config/logistics_nav.php`, next to Reports.

| Page | Purpose |
|---|---|
| **Cash Collections** (built) | Rider cash ledger (parcels holding cash, cash on hand, in discrepancy, earnings accrued), receiving a Rider's cash with a shortage or overage flag, sending verified cash to the Province Hub as a batch, and recent receipts and sent batches. Rider earnings are a column of the ledger rather than a separate page. |
| **Cash Discrepancies** (built) | Open and resolved discrepancies with receipt notes and a required resolution note. |

### 7.3 Province Hub (web, `lgp.province.*`)

| Page | Purpose |
|---|---|
| **Cash Remittances** (built) | Confirm or flag batches from its Station Hubs, resolve discrepancies, and send verified cash to the Company as a batch. |

### 7.4 Logistics Company (web, `lgp.company.*`)

| Page | Purpose |
|---|---|
| **Rider Payment** (built) | Set and update the fixed pay per delivered parcel, with a worked example. |
| **Cash Remittances** (built) | Confirm cash from Province Hubs, resolve discrepancies, see Company earnings, Rider pay held and Vendo's delivery commission on confirmed cash, and send Vendo what it is owed (item price plus Vendo's delivery commission). |

### 7.5 Vendo Admin (web, `admin.*`)

| Page | Purpose |
|---|---|
| **Delivery Fees** (built) | Set the delivery commission % and the seller commission % (super admin only), with a live preview, delivery commission earned, and a history of per-parcel distributions. Restores the rate editor that was removed. |
| **Cash Remittances** (built) | Confirm cash received per Logistics Company batch, flag and resolve discrepancies. |

### 7.6 Seller (web, `seller.*`)

- **Earnings** (built): item price, voucher, commission and net earnings per order, with **Pending** (Vendo has not yet confirmed the cash) and **Credited** (Vendo confirmed it; credited automatically) totals, a status filter and pagination.

### 7.7 Buyer (web, `buyer.*`)

- Checkout shows "Pay ₱X in cash to the rider" in the payment method card, and the order page shows "Please have ₱X ready in cash for the rider" until the order is delivered, cancelled or returned (built).

## 8. Implementation order

1. Settle the open items in section 9.
2. Add migrations for settings, snapshots, `cod_amount`, collections and remittances.
3. Rewrite the settlement service to the new split and snapshot the rates at checkout.
4. Record the collected amount at delivery (web API here; mobile change in `E-Comm_Mobile`).
5. Station Hub pages, then Province Hub, then Company.
6. Admin pages.
7. Seller earnings page and Buyer COD notice.
8. Returns, failed deliveries and reversals.

Each step follows the `AGENTS.md` loop: record the work in `docs/logs/PROGRESS-YYYY-MM-DD.md`, update the matching `docs/features/**/spec.md`, and update `docs/domain-feature-status.md`, `docs/schema.md` and `docs/architecture.md` when they become stale. Never mark a placeholder as implemented.

## 9. Open items

| Item | Recommendation |
|---|---|
| **When the Seller is credited.** | Automatically, when Vendo confirms receipt of the remittance that contains the parcel. Until then the Seller sees the earnings as Pending. |
| **Delivery fee amount.** | Keep the current ₱45 + ₱10-per-extra-stop formula for now; only the split changes. |
| **Which company's rider pay applies.** | Use the rate of the Logistics Company resolved at checkout, snapshotted on the order. |
| **Cross-company routes.** | Decide how the Logistics share is divided, or whether the collecting company holds it. Today it is reported as unallocated. |
| **Voucher discounts and seller commission.** | Assumed in code: Seller vouchers are Seller-funded and the commission applies to the gross item price, so earnings are items minus voucher minus commission. Confirm or change. |
| **Return shipping and refunds.** | Decide who pays return shipping and whether a returned parcel reverses any fee already recorded. |
| **Cash shortages.** | Decide who is liable for a Rider shortage and how it is recovered. |
| **Remittance frequency.** | Daily or weekly batches per hub level. |

## 10. Code references

- `app/Services/CheckoutShippingQuoteService.php`: delivery fee quote.
- `app/Services/ShippingEarningsSettlementService.php`: settlement on delivery.
- `app/Services/OrderScanWorkflow.php`: delivered scan that triggers settlement.
- `app/Http/Controllers/Api/RiderDeliveryProofController.php`: delivery proof.
- `app/Http/Controllers/Api/RiderEarningsController.php`: Rider earnings API.
- `app/Http/Controllers/Buyer/CheckoutController.php`: order creation and fee validation.
- `app/Models/Ecommerce/Order.php`: buyer payable and fillable money fields.
- `app/Models/Finance/CommissionSetting.php`, `app/Http/Controllers/Admin/CommissionController.php`: fixed 10% commission.
- `config/logistics_nav.php`, `routes/logistics_portal.php`: portal navigation and routes.
- `database/migrations/2026_10_23_220000_add_shipping_quotes_and_earnings.php`: current shipping settlement tables.