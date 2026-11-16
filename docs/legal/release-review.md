# Public policy release review

**Reviewed:** 16 November 2026

**Scope:** The [Terms and Conditions](terms-and-conditions.md) and [Privacy Policy](privacy-policy.md) are the Markdown source for public web routes in this repository. The owner requested links from web registration. This review records remaining product and legal work; a deployed page does not by itself establish legal compliance.

## Decisions reflected in the drafts

- The copy now uses the current actor names: Buyer, Seller, Logistics Company, Province Hub, Station Hub, Rider, and Transport Driver. Riders register with Motorcycle, Van, or L300 vehicles; company trucks are assigned to Transport Drivers. The previous "Logistics Center" and "Truck Rider" wording is not used for new accounts.
- The terms describe the current order lifecycle: cash on delivery, server-quoted route fees, vouchers, delivery scans and proof, Buyer receipt confirmation, the seven-day return request window, Seller approval or decline, return pickup, and the fixed return pickup fee. The copy is careful not to promise that a recorded refund has already been paid.
- The privacy copy covers saved addresses and map pins, Geoapify/OpenStreetMap map requests, delivery-proof photos, support-ticket attachments, complaint evidence, account-deletion requests, and Rider Firebase push tokens. It keeps the existing statement that scan records do not create a continuous GPS trail.
- The public legal pages now use the white-on-purple `assets/branding/log-in-logo.svg` asset inside a purple brand chip, so the logo remains legible on the legal header.
- The owner confirmed a **10%** platform commission. `CommissionSetting::currentRate()` defaults to 10.00 and the migration also defaults to 10.00. The Admin rate control can change the stored value; the live configured rate must be checked before publishing a fixed 10% promise. Admin commission reports currently calculate from gross item price × quantity for orders in `delivered` or `completed` status. Sellers do not yet see that rate or a commission balance in their own report. These are reports, not a payout ledger.
- Logistics Company and hub staff manually assign Riders and Transport Drivers. Rider and Driver interfaces remain mobile-only. Scans report status and the recorded facility or time; Buyer receipt confirmation and product rating are separate. Continuous GPS delivery tracking is not part of the current workflow.
- New web and Rider registrations can verify email by OTP or server-verified Google ID token. A new Google identity continues through the normal role registration and approval steps. Google account ID is not stored as a separate user-profile field.
- Checkout currently supports cash on delivery. Real-facility scans are the current tracking source; the legacy SH scanner and virtual checkpoint data remain separate and are not described as the current physical-custody workflow.

## Open follow-ups

1. Web URLs and Buyer, Seller, and Logistics Company registration links are implemented in code. Connect the separate mobile registration screens to these pages and decide how to record policy version and acceptance; the current `agree_terms` check has no versioned consent record.
2. Enforce the owner-approved adult eligibility rule in registration and build a meaningful guardian consent path for minor Buyers, or revise the minor exception. Current birthday validation only checks that the date is before today; it does not enforce age or record guardian consent. The copy also states that Riders, Drivers, and hub managers are adults, but the server does not yet enforce every one of those roles.
3. Remove plaintext `users.temp_password_plain` persistence. Buyer, Seller, Logistics, and Rider verification uploads now use private storage and authorized review routes; run `php artisan verification:privatize` in any environment with legacy public IDs or permits. Keep private files on persistent storage when deploying. The [security overview](../security.md) records the remaining credential risk. Do not claim universal hashed-credential storage until it is fixed.
4. Define category-specific retention/deletion periods and the account closure and privacy-request process; identify the legal entity or person acting as Vendo's personal information controller and its contact details. Confirm actual hosting, email, and Google-provider disclosures for the deployed service.
5. Confirm the current database's configured commission rate is 10%, because the Admin screen may override the default. The owner confirmed the intended current rate and will update the Terms if it changes. Review how a future rate change is notified to Sellers and how reversals/refunds affect reports before making stronger settlement promises.
6. Obtain legal/privacy review of the liability, minors, notices, returns/refunds, and data-subject-rights language. The public copy reflects current product understanding, but repository inspection alone cannot establish legal compliance. In particular, the copy must not be read as a promise that a recorded refund or Seller earning has been paid.

The owner performs product testing. This 16 November pass changed the legal Markdown, the legal header logo and its CSS, and the related documentation only. It made no migration, route, database, or test change. `npm run build` and `php artisan view:cache` passed. No browser walkthrough or device check was run, and deployment has not been checked.

Owner browser and deployment verification remain pending. The public copy is aligned with the code inspected on 16 November 2026, but it is not a substitute for legal review.
