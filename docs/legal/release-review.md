# Public policy release review

**Reviewed:** 28 September 2026  
**Scope:** The [Terms and Conditions](terms-and-conditions.md) and [Privacy Policy](privacy-policy.md) are the Markdown source for public web routes in this repository. The owner requested links from web registration. This review records remaining product and legal work; a deployed page does not by itself establish legal compliance.

## Decisions reflected in the drafts

- The owner confirmed a **10%** platform commission. `CommissionSetting::currentRate()` defaults to 10.00 and the migration also defaults to 10.00. The Admin rate control can change the stored value; the live configured rate must be checked before publishing a fixed 10% promise. Admin commission reports currently calculate from item price × quantity for orders in `delivered` or `completed` status. Sellers do not yet see that rate or a commission balance in their own report. These are reports, not a payout ledger.
- Logistics Center staff manually assign Riders. Rider acceptance/decline, first-come allocation, and continuous GPS delivery tracking are not part of the current live workflow. Rider scans report status and hub/time; Buyer receipt confirmation and product rating are separate.
- New web and Rider registrations can verify email by OTP or server-verified Google ID token. A new Google identity continues through the normal role registration and approval steps. Google account ID is not stored as a separate user-profile field.
- Checkout currently supports cash on delivery. The planned SH scanner and physical custody workflow is not yet a live source of tracking events.

## Open follow-ups

1. Web URLs and Buyer, Seller, and Logistics Center registration links are implemented in code. Connect the separate mobile registration screens to these pages and decide how to record policy version and acceptance; the current `agree_terms` check has no versioned consent record.
2. Enforce the owner-approved adult eligibility rule in registration and build a meaningful guardian consent path for minor Buyers, or revise the minor exception. Current birthday validation only checks that the date is before today; it does not enforce age or record guardian consent.
3. Remove plaintext `users.temp_password_plain` persistence. Buyer, Seller, Logistics, and Rider verification uploads now use private storage and authorized review routes; run `php artisan verification:privatize` in any environment with legacy public IDs or permits. Keep private files on persistent storage when deploying. The [security overview](../security.md) records the remaining credential risk. Do not claim universal hashed-credential storage until it is fixed.
4. Define category-specific retention/deletion periods and the account closure and privacy-request process; identify the legal entity or person acting as Vendo's personal information controller and its contact details. Confirm actual hosting, email, and Google-provider disclosures for the deployed service.
5. Confirm the current database's configured commission rate is 10%, because the Admin screen may override the default. The owner confirmed the intended current rate and will update the Terms if it changes. Review how a future rate change is notified to Sellers and how reversals/refunds affect reports before making stronger settlement promises.
6. Obtain legal/privacy review of the liability, minors, notices, and data-subject-rights language. The public copy reflects current product understanding, but repository inspection alone cannot establish legal compliance.

The owner performs product testing. The earlier policy-draft pass made no code, migration, or live database changes. A later security change moved 11 local legacy verification files from public to private storage after hash verification; no database migration was needed. Owner browser and deployment verification remain pending.

The public web routes and registration links are code changes only in this pass; deployment has not been checked. No migration, tests, build, or browser walkthrough were run by the agent.
