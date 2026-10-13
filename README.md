# Lab Automation System

A lightweight plain-PHP/MySQL laboratory tracking app for electrical products. Laravel is not used. Existing repository pages are being migrated incrementally into a shared MVC/layout foundation rather than replaced in one risky rewrite.

## Current structure

- `config/` — environment access, centralized route-role policy, and security helpers
- `controllers/`, `models/`, `middlewares/` — request handling, database/business logic, authentication, roles, and CSRF
- `views/` — reusable authenticated/public layouts, components, and selected page templates
- `assets/css/theme-tokens.css` — persistent dark-teal/light theme tokens used by page styles
- `resources/css/`, `resources/js/`, `vite.config.js` — Tailwind CSS, local Font Awesome and Inter assets, shared interactions, and Vite build
- `database/migrations/`, `scripts/` — versioned schema upgrades, legacy compatibility checks, and demo seed
- Root PHP pages retain their existing URLs. Legacy screens now share the same authenticated sidebar, workspace bar, theme control, role policy, and responsive content alignment while their page-specific content is migrated in steps.

## Requirements

- PHP 8.2+ with `mysqli`, and MySQL/MariaDB
- Node.js 20.19+ and npm for the Tailwind/Vite asset build
- Apache/XAMPP or PHP's built-in server for local testing

## Local setup

1. Copy `.env.example` to `.env`. Use a private local database account; update `DB_NAME`, `DB_USER`, and `DB_PASSWORD` as needed. Never put real credentials in chat or GitHub.
2. For a **fresh** XAMPP/phpMyAdmin demo database, back up any target first, import `database/project_lab_db_import.sql`, and make sure `.env` uses `DB_NAME=project_lab_db`. For your **existing `project_lab_db` with real/current rows**, do not re-import the demo dump: back up first, then run `database/manual_upgrade_existing_project_lab_db.sql` in phpMyAdmin. For CLI setup, run `php scripts/migrate.php`. These additive upgrades do not drop/recreate tables or delete existing rows; review any duplicate/orphan diagnostics before relying on a skipped unique index or foreign key. See `database/IMPORT.md`.
3. For disposable local/demo data only, run `php scripts/seed_demo.php`. Do not seed synthetic measurements into a real lab database.
4. Install/build frontend assets:
   ```bash
   npm ci
   npm run check:js
   npm run build
   ```
5. Serve the project through Apache/XAMPP, or use `php -S 0.0.0.0:8080 -t .` for a quick local smoke test. Open `index.php`.

On 2026-10-12, `npm run check:js`, the Vite production build, and PHP grammar parsing of 81 files passed. A native PHP runtime, MySQL/MariaDB, and browser screenshot runner are unavailable in this workspace, so route/SQL execution, database preservation, and visual acceptance remain unverified. The current source includes the approved sequential assignment workflow and additive migration 010. Review `tests/SRS-ACCEPTANCE-MATRIX.md`; back up the existing database, then use the manual additive upgrade or migration runner—not the demo import.

## Public pages and contact/registration behavior

- `index.php`, `about.php`, and `contact.php` are public information pages.
- `register.php` requires a unique email and creates a basic **Tester** login with a fresh linked tester profile; self-registration cannot choose Manager, Quality Control, or Administrator. Because this app does not verify signup email, public registration cannot claim a pre-existing tester profile—an Administrator must review and link it. A new Tester is signed in after successful registration.
- `contact.php` validates and saves messages in `contact_messages`. **It does not send email**; an administrator must review submissions through the database until a review screen/email delivery is added.

## Authentication and authorization

- Login accepts a username or the unique stored email and verifies the password with PHP `password_verify`; public registration requires an email and stores it on both the account and its linked Tester profile. Passwords are stored with `password_hash`.
- Successful login regenerates the PHP session ID. Cookies are `HttpOnly` and `SameSite=Lax`, with `Secure` enabled when served over HTTPS. Idle sessions expire after eight hours.
- On authenticated legacy requests, `db.php` reloads the account's active state and role. Deactivated accounts are logged out, and role changes apply immediately.
- Every protected page calls `require_page_access()` and is checked against `config/permissions.php`. `views/layouts/sidebar.php` and `legacy_sidebar.php` use the same matrix to hide unavailable links. Hidden links are only UX; the server-side guard is the security boundary.
- State-changing forms use the session-bound CSRF token. Tester product lists/details, test lists/details, dashboard stats, search results, and product selectors are scoped to the linked tester profile/participant assignments. A Tester can complete an existing pending assignment without creating a duplicate record.
- Optional Google sign-in uses server-side OAuth code exchange, one-time state, PKCE, and Google's verified UserInfo response. An existing account with the matching verified email is linked without changing its role; a first-time Google identity receives the least-privileged Tester role and a linked profile. See `docs/GOOGLE-OAUTH-SETUP.md`.
- Administrator account edits preserve the account ID, protect the final active Administrator, synchronize Tester profiles, and deactivate rather than delete accounts. Migration 008 adds `users.role_id → roles.id`; legacy `users.role` remains during the compatibility transition. Migration 009 adds manual CPRI result and market-release fields; migration 010 adds account phone details and per-user dashboard preferences. Unique account emails and one-to-one Tester-profile links are enforced only when existing data is unambiguous. Product archive/restore keeps test/workflow history. Administrator test corrections preserve before/after data in the audit trail and keep generated IDs and test assignments immutable.
- Each role can edit their own Profile and save theme, compact-dashboard, and reduced-motion preferences. This is separate from Administrator-only lab-wide Settings.
- Tracked sign-ins, account/profile changes, catalogue/test-plan edits, product/test corrections, archive actions, and workflow transitions appear in the Administrator Activity Log after migration 007 is applied.

| Role | Intended access |
|---|---|
| Administrator | Full lab operation, pending-test assignment, account/role management, product catalogue, family test-plan setup, and workflow controls |
| Lab Manager | Register/edit products, assign pending tests to one or more available Testers, edit open pending-test instructions, configure day-to-day lab setup, and monitor results; no rework/CPRI workflow or user/role administration |
| Tester | Complete only pending tests assigned by an authorized Lab Manager, Administrator, or QC user, using their linked tester profile; no test creation, global reports/status, or staff configuration |
| Quality Control | Review lab tests/status/reports, assign pending tests and retests to available Testers, confirm physical rework, record manual CPRI results, and release approved products; no lab setup/accounts |

This role table describes the current route policy, not an external enterprise identity provider. Admins should remove/change demo accounts before sharing the system.

## Local demo accounts

The demo seed creates `admin` (Administrator), `manager` (Lab Manager), `tester` (Tester), and `quality` (Quality Control) for local testing only. Keep demo credentials out of shared environments; replace or remove these accounts before deployment. Seeded measurements and CPRI handoff rows are synthetic, not certification data.

## SRS implementation notes

- Product families, exact product/model codes, departments, test types, and each family's required/optional test plan are database-driven.
- Product ID uses the selected sprint split: exact model mapping (2 digits) + revision (2) + manufacturing sequence (6). Test ID uses the first four Product ID digits + test numeric code (3) + a locked per-product/per-test-type roll (5). Confirm the institutional format before using real records.
- Invalid ID segments are rejected rather than silently truncated. Unique indexes protect Product ID/Test ID, and concurrent roll allocation is serialized.
- Test records retain criteria, expected output, actual output, result, remarks, date, routed department, and one or more named testers. Administrators, Lab Managers, and QC can create a pending assignment; the linked Tester records actual output and PASS/FAIL on that assignment through `complete-test.php`.
- Testers cannot assign tests and can submit PASS/FAIL only for their own eligible pending records in the product's current cycle. A product with an open current-cycle test cannot receive another assignment; after it closes, only mapped test types not already recorded in that cycle are eligible. Busy/inactive Testers are disabled in the picker and checked again under transaction locks.
- A failed lab test or CPRI rejection routes the product to re-manufacture. After Quality Control records physical rework completion, an authorized assigner can choose the next unused mapped test in the new cycle; required tests still gate CPRI readiness and earlier-cycle passes do not satisfy the new cycle. Administrators retain full workflow access.
- Quality Control owns rework completion, manual CPRI handoff, CPRI result recording, and market-release recording. A CPRI PASS first changes the product to `CPRI Approved`; market release is a separate action. A CPRI FAIL routes to `Failed - Re-manufacturing` and keeps the external result in its original handoff history.
- CPRI remains external: no API or automatic transfer is configured. Each handoff/result/release date, actor, reference, and optional notes are stored locally in `cpri_handoffs` and workflow history.
- Product catalog, product test plan, product workflow, product register, dashboard, testing list, roles, departments, and remaining legacy CRUD pages use the same authenticated navigation and workspace header. Legacy page content is still being migrated incrementally; the compatibility shell keeps its layout, theme colors, and responsive alignment consistent in the meantime.

## Security / deployment reminder

Do not commit `.env`, database passwords, provider certificates, or real production records. Do not expose a MySQL administrator account to the internet. Use a restricted application user, TLS, provider firewall/IP controls, backups, and a real security review before production use.
