# Import the complete demo database

`project_lab_db_import.sql` is a self-contained MySQL/MariaDB schema-and-demo-data file for phpMyAdmin. It creates missing application tables and conditionally adds common compatibility columns to existing project tables, including `products.product_code_id` and `tests.department_id`; it does not drop existing rows. It includes built-in roles, demo users, and synthetic samples covering pending, in-progress, CPRI-ready, re-manufacturing, and handed-off states. These are not real test measurements or an actual CPRI transfer.

## XAMPP/phpMyAdmin setup

1. Back up your current database first.
2. In phpMyAdmin, open the database used by the app (the file targets `project_lab_db`; `.env` must use the same `DB_NAME`).
3. Open **Import**, choose `project_lab_db_import.sql`, and run it. The compatibility section checks each column before adding it, so importing the file again will not fail on already-added columns.
4. For a default local XAMPP install, `.env` commonly uses `DB_HOST=127.0.0.1`, `DB_NAME=project_lab_db`, `DB_USER=root`, and an empty `DB_PASSWORD`.
5. Open `index.php` through Apache/XAMPP. If an old page still reports a missing column, run `php scripts/migrate.php` from the project folder so the migration runner can check the whole schema.

## Demo sign-ins

All four accounts use password `LabDemo@123` (demo only):

| Username | Role |
|---|---|
| `admin` | Administrator |
| `manager` | Lab Manager |
| `tester` | Tester |
| `quality` | Quality Control |

Change these passwords or remove the accounts before using any real records. The SQL contains password hashes, not plaintext passwords.

## Existing database warning

The full import is for a new/empty database. It does **not** drop existing tables, but `CREATE TABLE IF NOT EXISTS` cannot repair an old table definition. If you already have tables or data, make a backup first, then run the versioned upgrade instead:

```bash
php scripts/migrate.php
php scripts/seed_demo.php
```

Do not import this dump into real laboratory data without a backup and a reviewed migration plan. Do not overwrite a teammate's `.env` or use shared root credentials.
