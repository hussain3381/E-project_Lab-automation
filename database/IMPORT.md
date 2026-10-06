# Import the complete demo database

`project_lab_db_import.sql` is a self-contained MySQL/MariaDB schema-and-demo-data file for phpMyAdmin. It includes all current application tables, the built-in roles, the default demo accounts, product/test catalogues, and synthetic samples covering pending, in-progress, CPRI-ready, re-manufacturing, and handed-off states. These are not real test measurements or an actual CPRI transfer.

## Fresh local setup

1. In phpMyAdmin, create/select a database named `project_lab_db` (the SQL file also creates it if permitted).
2. Open **Import**, choose `project_lab_db_import.sql`, and run the import. Alternatively, paste/run the file in the SQL tab.
3. Copy `.env.example` to `.env`; set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` to match XAMPP. For a default local XAMPP install, `DB_NAME=project_lab_db`, `DB_USER=root`, and an empty `DB_PASSWORD` are common defaults.
4. Open `index.php` through Apache/XAMPP.

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
