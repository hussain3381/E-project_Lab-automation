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

The import is idempotent for the supplied schema and uses `INSERT IGNORE` for demo records. It does **not** drop existing tables or rows. Its compatibility block adds known missing columns, but `CREATE TABLE IF NOT EXISTS` cannot repair every custom/legacy table definition. For a database that already has real or shared data, back it up and review the migration plan; prefer the versioned CLI migration and seed only a disposable local database:

```bash
php scripts/migrate.php
# Optional, only for a throwaway/demo database:
php scripts/seed_demo.php
```

After importing into an existing database, run `php scripts/migrate.php` so the runner can add the safe indexes/foreign keys and verify the compatibility fields. Do not import demo rows into real laboratory data without a backup and reviewed migration plan. Do not overwrite a teammate's `.env` or use shared root credentials.
