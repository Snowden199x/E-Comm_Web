# Vendo

Vendo is a server-rendered marketplace and commerce operations web application. It provides marketplace features for buyers and sellers, with operational workspaces for administrators and logistics centers.

## Stack

- Laravel 13 and PHP 8.3
- Blade views and Laravel session authentication
- Vite, JavaScript, CSS, and Alpine.js
- Eloquent ORM and Laravel migrations
- MariaDB for local development; the Laravel configuration also supports SQLite and other database drivers

The courier role has a limited backend API contract. The rider app is maintained in a separate repository. See the implementation status docs for current workflow limits and planned work.

## Requirements
- PHP 8.3 with Composer (follow the PHP constraint in `composer.json`)
- Node.js and npm
- MariaDB and PHP's `pdo_mysql` extension
- DBeaver Community or another SQL client (optional, for managing the database)

## Local setup

1. Install dependencies from the repository root:

   ```bash
   composer install
   npm install
   ```

2. Create an ignored local `.env` file and configure the app URL, database, session, mail, and other local services. The repository does not include an `.env.example` file. Never commit `.env` or put real credentials in this README.

3. Start the MariaDB service. In DBeaver, connect to that service and create a local database, for example:

   ```sql
   CREATE DATABASE vendo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Configure Laravel to use the same MariaDB connection in `.env`:

   ```dotenv
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=vendo
   DB_USERNAME=your_local_database_user
   DB_PASSWORD=your_local_database_password
   ```

   Replace the username and password with the local account you use in DBeaver. DBeaver is the database client; the MariaDB service must also be running.

4. Generate the local application key:

   ```bash
   php artisan key:generate
   ```

5. Apply the database migrations:

   ```bash
   php artisan migrate
   ```

   Populate the Seller registration and product category list after a new database setup:

   ```bash
   php artisan db:seed --class=CategorySeeder
   ```

   `migrate` creates the category table but does not insert category rows. The category seeder can be run again without duplicating its existing categories.

6. Start the Laravel server and Vite in separate terminals:

   ```bash
   php artisan serve
   ```

   ```bash
   npm run dev
   ```

   To compile production assets, run `npm run build`.

### Local admin account

For a local development database, set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`, then run:

```bash
php artisan db:seed --class=AdminSeeder
```

The seeder creates or updates a super administrator using those values. Keep them local and use a strong password.

## Project structure

| Path | Purpose |
|---|---|
| `app/` | Controllers, models, services, middleware, and application logic |
| `routes/` | Web and API route definitions |
| `resources/views/` | Blade pages and reusable view components |
| `resources/css/`, `resources/js/` | Vite-managed frontend assets, organized by role and shared features |
| `database/migrations/`, `database/seeders/` | Database schema changes and development seeders |
| `docs/` | Architecture, domain status, workflow decisions, feature specifications, and progress notes |

## Documentation

- [Documentation index](docs/README.md)
- [Current feature status](docs/domain-feature-status.md)
- [Architecture](docs/architecture.md)
- [Database schema](docs/schema.md)
- [Order and logistics flow decisions](docs/order-logistics-flow-decisions.md)
- [Development workspace guide](docs/Development-Workspace-&-Task-Planning-guide.md)
- [Contribution and repository rules](AGENTS.md)

Read the relevant feature specification under `docs/features/` before changing a workflow. The status matrix distinguishes implemented behavior from partial workflows and placeholders.

## Tests

Run the Laravel test suite with:

```bash
php artisan test
```

## Security

Do not commit `.env`, credentials, private verification documents, or production data. Configure production URLs and proxy settings according to [the architecture guide](docs/architecture.md).
