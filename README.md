# Lab Automation System

A plain-PHP/MySQL laboratory tracking application for electrical products. Laravel is not used. The existing repository screens are being migrated incrementally into MVC so the working UI is not discarded in a risky one-shot rewrite.

## Current structure

- `config/` — environment, database, session, and security configuration
- `controllers/` — login, catalogue, test-plan, and product-workflow request handling
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
2. Create the database once on that MySQL server, then run `php scripts/migrate.php` once from the command line. This applies only new versioned migrations. For a local demo, run `php scripts/seed_demo.php` as well. A teammate does not re-import a database dump on every laptop; each local copy connects to the same server using private `.env` values.
3. Install frontend build dependencies with `npm ci`, then run `npm run check:js` and `npm run build`. The PHP app uses the generated local files in `assets/compiled/`; page-specific CSS is loaded locally.
4. Point Apache/XAMPP at this project and open `index.php` in the browser.

Do not commit `.env`, database passwords, provider certificates, or real production records. Do not expose a MySQL administrator account to the internet. Use a restricted application user, TLS, and provider firewall/IP controls.

## Local demo login

After loading the demo seed, the local demonstration account is `admin` with password `LabDemo@123`. It is for a temporary demo only. Change/remove it before any shared or production deployment; never reuse it for real laboratory data.

## SRS implementation notes

- Product families, exact product/model codes, departments, test types, and each family's required/optional test plan are database-driven.
- Product ID uses the user-selected sprint split: exact model mapping (2 digits) + revision (2) + manufacturing sequence (6). Test ID uses the first four Product ID digits + test numeric code (3) + locked per-product/per-test-type roll (5). The instructor/supervisor should still confirm the institutional format before real records are used.
- Invalid ID segments are rejected rather than silently truncated. Unique indexes protect Product ID/Test ID, and concurrent roll allocation is serialized.
- Test records retain criteria, expected output, actual output, result, remarks, date, routed department, and one or more named testers.
- A failure routes the product to re-manufacture; after rework is recorded, retesting starts a new cycle. The CPRI gate only opens once every required test in that current cycle passes.
- CPRI is external. `Product Workflow` records readiness and a manual handoff timestamp/user/reference; no CPRI API or automatic transfer is configured.
- Migrations and the demo seed are CLI commands. Demo credentials/data are for throwaway testing only.
