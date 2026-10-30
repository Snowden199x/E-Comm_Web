# Vendo Web Test Suite

**Reviewed:** 29 October 2026

## Run the PHP web suite

From the Vendo web repository root, run:

```bash
php artisan test
```

The command runs both PHPUnit suites configured in `phpunit.xml`: `tests/Unit` and `tests/Feature`. The test environment uses an in-memory SQLite database, array cache/session/mail settings, and does not connect to the local MariaDB database or the production database. Migrations run against SQLite as part of database-refreshing feature tests.

## Current feature coverage

- **Admin:** admin-guard sign-in, rejection of non-admin credentials, and access to registration review and user management.
- **Buyer:** OTP-backed registration proof, login/logout, account settings, password reset/update, and buyer isolation from Admin pages.
- **Seller:** order preparation and cancellation behavior, ownership/approval checks, dashboard figures, order filtering, and read-only order details.
- **Logistics:** Company, Province Hub, and Station Hub portal access, plus rejection of unrelated Buyer accounts; Station pickup assignment expiry (an unscanned assignment is released and notifies both sides, while fresh and already-started tasks are untouched); Rider notification feed (only the signed-in Rider's rows, unread count and `since` watermark, single and bulk read state, foreign-row 404, and rejection of requests without an approved Rider token).
- **Shared database behavior:** legacy order-status conversion and migration compatibility with the SQLite test driver.

The suite is a role-level regression baseline, not exhaustive coverage of every route or workflow. New feature work should add tests for successful behavior, validation/state conflicts, wrong-role access, tenant/facility isolation, and transactional rollback where those cases apply. Rider mobile tests belong to the separate mobile repository and are outside `php artisan test` here.

The repository has no `npm test` script. `tests/Frontend/buyer-registration.test.cjs` is not run by `php artisan test`.

## Current verification

On 30 October 2026, after merging the Buyer mobile API work with the daily delivery window and Rider notification work, `php artisan test` completed with **46 passed and 5 failed** (183 assertions). The 5 failures are the pre-existing `GD extension is not installed` registration tests in `BuyerRegistrationProgressTest`; they are environment failures, not regressions. This is local test-suite evidence only; it does not verify production MySQL/MariaDB behavior, browser rendering, mail delivery, or deployed services.
