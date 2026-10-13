# Import and upgrade database

`project_lab_db_import.sql` is the complete MySQL/MariaDB schema-and-demo-data file for a **fresh, disposable demo database**. It creates missing application tables and conditionally adds common compatibility columns. Synthetic workflow samples cover pending, in-progress, CPRI-ready, re-manufacturing, and handed-off states; the demo handoff waits for a manually recorded CPRI result. None of these are real test measurements or an actual CPRI transfer.

## Fresh demo database only

1. Back up the target database first.
2. In phpMyAdmin, select the database configured for the app in your private `.env` file (the import file targets `project_lab_db`).
3. Import `project_lab_db_import.sql`. Its compatibility section checks existing columns before adding them, so a repeat import will not fail on already-added columns.
4. Configure your own local `.env` values for the database host, database name, username and password. Do not share or commit credentials.
5. Open the app through your local PHP server. If a page reports a missing column, use `php scripts/migrate.php` to apply/check the current schema.

## Demo sign-ins

All four disposable accounts use password `LabDemo@123`:

| Username | Role |
|---|---|
| `admin` | Administrator |
| `manager` | Lab Manager |
| `tester` | Tester |
| `quality` | Quality Control |

Change these passwords or remove the accounts before using any non-demo records. The SQL contains password hashes, not plaintext passwords.

## Existing database — preferred safe upgrade

If you already have `project_lab_db` with your own records, **do not re-import `project_lab_db_import.sql` or the demo seed**. First export a backup in phpMyAdmin (**Export → Quick → SQL**), then run `manual_upgrade_existing_project_lab_db.sql` against the existing database. It is additive and idempotent: it keeps existing rows and adds missing compatibility fields/tables and account/profile relations when the existing data is unambiguous. It does not drop or recreate existing tables, delete rows, or load demo data.

The manual upgrade creates or extends `cpri_handoffs` with CPRI result, actor/date/notes and market-release actor/date/notes fields. It also adds account phone details and a separate `user_preferences` table for each user’s theme, compact dashboard, and reduced-motion choices. Existing handoff rows are preserved and receive `PENDING` as the default result; do not treat the default as an actual CPRI outcome. The final queries report duplicate emails, duplicate profile links, orphan links, and accounts whose legacy role string needs a matching role row. Review any conflicts intentionally, then rerun the script if it safely skipped a constraint.

For a CLI-connected database, `php scripts/migrate.php` applies versioned migrations through `010_user_profiles_and_preferences.sql`. It may stop with a duplicate-data error when an existing unique constraint cannot be added safely; do not remove records just to silence that error. Back up, review, and resolve any conflict with the database owner first.

## Important

The full import is for a fresh/throwaway demo database. Although it does not drop existing tables or rows, running it against a real/shared database is not the upgrade path. The optional `php scripts/seed_demo.php` command is only for disposable demo data. Do not overwrite a teammate's `.env`, paste credentials into chat/GitHub, or use shared root credentials.
