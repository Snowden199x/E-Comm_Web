# Development Workspace and Task Planning Guide

## Repository snapshot

Vendo is a Laravel web application using Blade views, Eloquent models, Laravel migrations, and Vite-managed JavaScript/CSS. Current route groups serve Admin, Buyer, Seller, and Logistics Center experiences. A Courier role and profile model exist, but a courier-facing web or mobile workflow is not implemented yet.

## Local development

1. Install PHP/Composer and Node.js/npm versions compatible with `composer.json` and `package.json`.
2. Install PHP dependencies with `composer install` and JavaScript dependencies with `npm install`.
3. Create a local `.env`, generate an application key, and configure a development database, cache, session, filesystem, and mail transport. Never commit `.env` or copy real credentials into docs, issue trackers, screenshots, or test fixtures.
4. Run `php artisan migrate` against the intended local database. This is additive schema work; do not use `migrate:fresh` against a database containing data you need.
5. Use separate terminals for `php artisan serve` and `npm run dev`, or build assets with `npm run build`.
6. Create an admin through the project’s documented local process or seeder; create seller demo data with `php artisan db:seed --class=SellerDemoSeeder` only in a local/testing environment. That seeder creates a new randomized demo batch on each run.
7. Run focused tests with `php artisan test`. Frontend checks currently include `tests/Frontend/buyer-registration.test.cjs`.

The actual credential names and service settings belong in local environment configuration and must not be copied into this guide.

## Before starting a task

- Read this guide, [domain-feature-status.md](domain-feature-status.md), the relevant domain page, and that feature’s spec.
- Inspect the route, controller, model, migration, view, and tests before changing behavior.
- Preserve existing teammate UI where the request is to connect a hardcoded page.
- Identify which actor owns each transition and which database records must change together.
- Keep work scoped. Do not mark adjacent placeholder links as implemented just because they share a page.

## Task completion checklist

- The documented behavior matches routes and server code.
- Authorization is enforced server-side and resources are scoped to their owner/tenant.
- Database changes have forward and rollback migrations where practical.
- Sensitive files are stored outside public paths and validated.
- Happy-path and important ownership/invalid-transition cases are tested.
- Assets build, views render, and the affected flow has been checked locally.
- Feature spec and domain status are updated; risks and external dependencies are recorded.
