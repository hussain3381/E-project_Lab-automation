# Lab Automation System

A lightweight plain-PHP/MySQL laboratory tracking app for electrical products. Laravel is not used. Existing repository pages are being migrated incrementally into a shared MVC/layout foundation rather than replaced in one risky rewrite.

## Current structure

- `config/` — environment access, centralized route-role policy, and security helpers
- `controllers/`, `models/`, `middlewares/` — request handling, database/business logic, authentication, roles, and CSRF
- `views/` — reusable authenticated/public layouts, components, and selected page templates
- `assets/css/theme-tokens.css` — persistent dark-teal/light theme tokens used by page styles
- `resources/css/`, `resources/js/`, `vite.config.js` — Tailwind CSS, local Font Awesome and Inter assets, shared interactions, and Vite build
- `database/migrations/`, `scripts/` — versioned schema upgrades, legacy compatibility checks, and demo seed
- Root PHP pages retain their existing URLs while the remaining legacy content is migrated in steps.

## Requirements

- PHP 8.2+ with `mysqli`, and MySQL/MariaDB
- Node.js 20.19+ and npm for the Tailwind/Vite asset build
- Apache/XAMPP or PHP's built-in server for local testing

## Local setup

1. Copy `.env.example` to `.env`. Use a private local database account; update `DB_NAME`, `DB_USER`, and `DB_PASSWORD` as needed. Never put real credentials in chat or GitHub.
2. For a fresh XAMPP/phpMyAdmin install, back up any target first, import `database/project_lab_db_import.sql`, and make sure `.env` uses `DB_NAME=project_lab_db`. For CLI setup or an existing database, run:
   ```bash
   php scripts/migrate.php
   ```
   The full import creates missing tables, adds known compatibility columns, and seeds synthetic examples without dropping existing rows. See `database/IMPORT.md` before importing into a database with existing data.
3. For disposable local/demo data only, run `php scripts/seed_demo.php`. Do not seed synthetic measurements into a real lab database.
4. Install/build frontend assets:
   ```bash
   npm ci
   npm run check:js
   npm run build
   ```
5. Serve the project through Apache/XAMPP, or use `php -S 0.0.0.0:8080 -t .` for a quick local smoke test. Open `index.php`.

The current repo has been checked with PHP lint, the JS syntax check, a Vite production build, MariaDB migrations, demo seed, and role/runtime smoke requests. Re-run them after changes. The CLI/server installed in the agent workspace is temporary; your own machine still needs the requirements above.

## Public pages and contact/registration behavior

- `index.php`, `about.php`, and `contact.php` are public information pages.
- `register.php` creates a basic **Tester** login and linked tester profile only; self-registration cannot choose Manager, Quality Control, or Administrator. A new Tester is signed in after registration.
- `contact.php` validates and saves messages in `contact_messages`. **It does not send email**; an administrator must review submissions through the database until a review screen/email delivery is added.

## Authentication and authorization

- Login looks up the username and verifies the password with PHP `password_verify`; passwords are stored with `password_hash`.
- Successful login regenerates the PHP session ID. Cookies are `HttpOnly` and `SameSite=Lax`, with `Secure` enabled when served over HTTPS. Idle sessions expire after eight hours.
- On authenticated legacy requests, `db.php` reloads the account's active state and role. Deactivated accounts are logged out, and role changes apply immediately.
- Every protected page calls `require_page_access()` and is checked against `config/permissions.php`. `views/layouts/sidebar.php` and `legacy_sidebar.php` use the same matrix to hide unavailable links. Hidden links are only UX; the server-side guard is the security boundary.
- State-changing forms use the session-bound CSRF token. Tester product lists/details, test lists/details, dashboard stats, search results, and product selectors are scoped to the linked tester profile/participant assignments. A Tester can complete an existing pending assignment without creating a duplicate record.

| Role | Intended access |
|---|---|
| Administrator | Full lab operation, account/role management, product catalogue, and family test-plan setup |
| Lab Manager | Product intake/editing, test types/departments/tester setup, test oversight, reports, and workflow; no user/role administration or admin-only catalogue/test-plan setup |
| Tester | Browse products linked to assigned work, search/open assigned tests, complete pending assignments, and record tests under their own linked profile; no global reports/status or staff configuration |
| Quality Control | Review lab tests/status/reports and operate the re-manufacture/CPRI workflow; does not author tests or manage lab setup/accounts |

This role table describes the current route policy, not an external enterprise identity provider. Admins should remove/change demo accounts before sharing the system.

## Local demo accounts

The demo seed creates `admin` (Administrator), `manager` (Lab Manager), `tester` (Tester), and `quality` (Quality Control). All use `LabDemo@123` for temporary testing. Change/remove them before any shared or production deployment. Seeded measurements and CPRI handoff rows are synthetic, not certification data.

## SRS implementation notes

- Product families, exact product/model codes, departments, test types, and each family's required/optional test plan are database-driven.
- Product ID uses the selected sprint split: exact model mapping (2 digits) + revision (2) + manufacturing sequence (6). Test ID uses the first four Product ID digits + test numeric code (3) + a locked per-product/per-test-type roll (5). Confirm the institutional format before using real records.
- Invalid ID segments are rejected rather than silently truncated. Unique indexes protect Product ID/Test ID, and concurrent roll allocation is serialized.
- Test records retain criteria, expected output, actual output, result, remarks, date, routed department, and one or more named testers. A Manager can assign work with a `PENDING` test record; the linked Tester records actual output and PASS/FAIL on that assignment through `complete-test.php`.
- A failure routes the product to re-manufacture; after rework is recorded, retesting starts a new cycle. The CPRI gate opens only once every required test in that current cycle passes.
- CPRI is external. `Product Workflow` records readiness and a manual handoff timestamp/user/reference; no CPRI API or automatic transfer is configured.
- Product catalog, product test plan, product workflow, product register, dashboard, and testing list now use the shared authenticated shell. Other legacy pages are still being migrated in steps.

## Security / deployment reminder

Do not commit `.env`, database passwords, provider certificates, or real production records. Do not expose a MySQL administrator account to the internet. Use a restricted application user, TLS, provider firewall/IP controls, backups, and a real security review before production use.
