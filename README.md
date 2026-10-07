# Lab Automation System

A plain-PHP/MySQL laboratory tracking application for electrical products. Laravel is not used. The existing repository screens are being migrated incrementally into MVC so the working UI is not discarded in a risky one-shot rewrite.

## Current structure

- `config/` — environment, database, role definitions, and security configuration
- `controllers/` — login, catalogue, test-plan, and product-workflow request handling
- `middlewares/` — authentication, role, and CSRF request guards
- `models/` — database access, ID generators, catalogue queries, and workflow rules
- `views/` — login, catalogue, test-plan, and workflow templates
- `assets/` — source CSS/JavaScript; the dark-teal and light themes use CSS variables
- `database/migrations/` — one-time, versioned database setup
- `tests/` — verification scripts and acceptance cases
- Existing root PHP screens remain temporarily as compatible legacy endpoints while they are migrated one by one.

## Requirements

- PHP 8.2 or newer with `mysqli`, MySQL/MariaDB, Node.js 20.19 or newer, and npm.
- For a local test, use XAMPP or an equivalent PHP/MySQL stack.

## Setup

1. Copy `.env.example` to `.env` and set the database values for your local or team-shared MySQL host.
2. For a local XAMPP/phpMyAdmin setup, back up first, select the database used by `.env` (default `project_lab_db`), and import `database/project_lab_db_import.sql`; it creates missing tables and conditionally adds common legacy columns. For a shared/team database, use `php scripts/migrate.php` and `php scripts/seed_demo.php` from the command line instead of importing demo rows over shared data.
3. `database/IMPORT.md` has the import steps and the demo accounts.
4. Install frontend build dependencies with `npm ci`, then run `npm run check:js` and `npm run build`. The PHP app uses the generated local files in `assets/compiled/`; page-specific CSS is loaded locally.
5. Point Apache/XAMPP at this project and open `index.php` in the browser.

Do not commit `.env`, database passwords, provider certificates, or real production records. Do not expose a MySQL administrator account to the internet. Use a restricted application user, TLS, and provider firewall/IP controls.

## Local demo logins

The demo seed creates `admin` (Administrator), `manager` (Lab Manager), `tester` (Tester), and `quality` (Quality Control). All four use `LabDemo@123` for temporary testing. Change/remove them before any shared or production deployment; never reuse them for real laboratory data.

## SRS implementation notes

- Product families, exact product/model codes, departments, test types, and each family's required/optional test plan are database-driven.
- Product ID uses the user-selected sprint split: exact model mapping (2 digits) + revision (2) + manufacturing sequence (6). Test ID uses the first four Product ID digits + test numeric code (3) + locked per-product/per-test-type roll (5). The instructor/supervisor should still confirm the institutional format before real records are used.
- Invalid ID segments are rejected rather than silently truncated. Unique indexes protect Product ID/Test ID, and concurrent roll allocation is serialized.
- Test records retain criteria, expected output, actual output, result, remarks, date, routed department, and one or more named testers.
- A failure routes the product to re-manufacture; after rework is recorded, retesting starts a new cycle. The CPRI gate only opens once every required test in that current cycle passes.
- CPRI is external. `Product Workflow` records readiness and a manual handoff timestamp/user/reference; no CPRI API or automatic transfer is configured.
- `AuthMiddleware`, `RoleMiddleware`, and `CsrfMiddleware` guard protected legacy and new routes; user accounts and their fixed built-in roles are managed by separate admin-only screens.
- Migrations and the demo seed are CLI commands. Demo credentials/data are for throwaway testing only.
