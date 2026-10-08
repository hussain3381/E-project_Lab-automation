# SRS acceptance / regression matrix

Run this only against a disposable local/demo database. Never run destructive cases on Aiven or real lab data. “Observed” values below include sandbox MariaDB 11.8 + PHP 8.4 checks on 2026-10-08.

| ID | Scenario / input | Expected result | Observed |
|---|---|---|---|
| AUTH-01 | Open a guarded page without a session | Redirect to login | PASS — 302 to `login.php` |
| AUTH-02 | POST without a valid CSRF token | Reject request | PASS — existing shared guard returns 419 |
| AUTH-03 | Log in as Tester and open admin/catalog/workflow routes | Return 403 where role is not allowed | PASS — all tested restricted pages returned 403 |
| AUTH-04 | Log in using each built-in demo role; open Users/Roles as non-admin | Correct session role; admin-only pages return 403 | PASS — all four roles authenticate; non-admin Users/Roles requests return 403 |
| AUTH-05 | Change a signed-in Manager to Tester in the database; request a Manager-only page | New role applies immediately without requiring logout | PASS — role reloaded from DB; Test Types returned 403 |
| AUTH-06 | Submit `role=Administrator` with a public registration form | Public input cannot elevate the account | PASS — account signed in as Tester and showed Tester dashboard |
| AUTH-07 | Tester opens an assigned and another Tester’s test detail | Assigned detail allowed; unassigned record denied | PASS — own detail 200, other Tester’s detail 403 |
| AUTH-08 | Tester opens product list/detail and a new-test product selector | Only products linked to assigned tests appear; unassigned product detail is denied | PASS — list and selector omitted unassigned Product ID; assigned detail 200; unrelated product detail 403 |
| PUB-01 | Register a new user with valid fields | Create Tester user + linked profile, then sign in | PASS — redirected to role-specific Tester dashboard; temporary account removed |
| PUB-02 | Submit Contact form | Persist message and redirect after success | PASS — saved to `contact_messages`, PRG worked; temporary message removed |
| ID-P-01 | Product code `01`, revision `01`, manufacturing `7` | `0101000007`; left-pad but never truncate | PASS — direct generator check |
| ID-P-02 | Bad product code/revision or 7-digit manufacturing value | Validation error; no product row | PASS — generator and `add-product.php` POST check |
| ID-P-03 | Registered `SWG01` mapped to code `01`, revision `02`, manufacturing `98` | Product ID `0102000098`; exact family/code saved | PASS — HTTP integration on clean and legacy-shaped schemas |
| ID-P-04 | Reuse an existing Product ID | Unique index / application rejects duplicate | PASS — HTTP duplicate Product ID POST rejected |
| ID-T-01 | Product prefix `0101`, test code `001`, first roll `00001` | Test ID `010100100001` | PASS — demo fixture + direct generator |
| ID-T-02 | Existing matching-layout test has roll `00001` | Next roll is `00002` | PASS — backfill check returned `010100100002` |
| ID-T-03 | Two simultaneous reservations for same product/type | Two distinct consecutive IDs | PASS — concurrent CLI calls returned `...00002` and `...00003` |
| ID-T-04 | Roll reaches `99999` | Throw overflow; do not wrap/truncate | PASS — overflow rejected and sandbox sequence restored |
| CAT-01 | Add department, product family, exact model mapping, numeric test code | Rows save with unique codes; ID segment is exact-model mapping | PASS — HTTP admin CRUD smoke test |
| CAT-02 | Product code family differs from selected family | Reject product save | PASS — mismatch POST rejected |
| PLAN-01 | Configure assigned/required tests for family | Save mapping and order | PASS — HTTP test-plan CRUD smoke test |
| TEST-01 | Save test with criteria, expected/actual, date, result and two testers | One test row, two participant rows, correct department and cycle | PASS — HTTP integration |
| TEST-02 | PASS only the first required test | Product remains `Testing In Progress`; CPRI blocked | PASS — HTTP integration; separate incomplete handoff rejected |
| FLOW-01 | Submit FAIL | Product becomes `Failed - Re-manufacturing`; failure/test status history is retained | PASS — HTTP integration |
| FLOW-02 | Add another test before rework release | Reject until an authorized workflow release | PASS — post-failure test POST rejected before rework release |
| FLOW-03 | Record re-manufacture complete, then retest | Product is released; new records have next `cycle_number`; rework event is audited separately | PASS — HTTP integration recorded cycle 2 and workflow event |
| CPRI-01 | Every required test in current cycle passes | Status becomes `CPRI Ready` | PASS — HTTP integration |
| CPRI-02 | Incomplete test plan, or one missing/non-PASS required type | Do not allow handoff | PASS — incomplete product handoff rejected |
| CPRI-03 | Mark manual handoff with optional reference | Store date, user, reference and a separate workflow event; make no API call | PASS — HTTP integration on clean and legacy-shaped schemas |
| SEARCH-01 | Search by Product ID/Test ID, routed department, cycle, participant tester | Matching records returned | PASS — participant + department + cycle HTTP query |
| UI-01 | Toggle dark/light and navigate between pages | Choice persists; page styles use shared tokens | PASS — saved theme helper syntax and local production assets build; visual/browser persistence check remains outstanding |
| UI-02 | Open Dashboard, Users, and Roles as Administrator; open role pages as Tester | Admin sees clear navigation; non-admin is denied; user form only offers registered roles | PASS — HTTP checks for links, user create/delete, role allow-list, and 403 behavior |
| UI-03 | Open `testing.php` against a legacy DB without `tests.department_id` | Page uses legacy test-type department fallback instead of fatal SQL error | PASS — HTTP 200 without the column; reran import and confirmed 200 after column restore |
| DB-01 | Run migrations on a clean schema twice | All migrations apply once; second run is no-op | PASS — clean install + idempotent rerun |
| DB-02 | Upgrade a legacy-shaped schema with `product_id VARCHAR(20)` and `test_id VARCHAR(30)`; seed then exercise workflow | Data/schema compatibility retained; IDs, rework, retesting, CPRI handoff and audit events work | PASS — latest migrations, seed, and full temporary workflow tested; temporary rows removed |
| DB-03 | Import `database/project_lab_db_import.sql` into a fresh database; import it again | Compatibility columns/table and migration markers exist; demo rows do not duplicate | PASS — fresh MariaDB import + second import; 4 users, 5 products, 7 tests, 6 migration rows, and `contact_messages` table confirmed |
| DB-04 | Apply `php scripts/migrate.php` to a clean database twice, then load demo seed | First run applies all migrations; second run is a no-op; demo fixtures load | PASS — 6 migrations applied, second run skipped all, seed reported 5 products / 7 tests |
| TEST-03 | Tester opens testing list/search and assigned/unassigned test details | Only linked profile/participant assignments appear; unassigned detail is denied | PASS — list/search omitted other Tester’s Test ID; own detail 200, other Tester detail 403 |
| TEST-04 | Tester submits PASS/FAIL for an assigned pending test | Update the existing assignment, persist measurements/date/remarks, update product workflow and audit; prevent duplicate submission | PASS — CSRF-protected PASS POST returned 303; row/workflow updated, then seeded fixture restored |
| USER-01 | Administrator creates a Tester from the user registry | Create account and linked tester profile atomically; no unlinked Tester login | PASS — temporary account/profile created and removed after verification |
| FRONT-01 | Run JS syntax check and Vite production build after local Inter/font changes | No JS errors; compiled Tailwind/Font Awesome/Inter files created | PASS — `npm run check:js && npm run build` |
| PHP-01 | Lint every repository PHP file | No syntax errors | PASS — PHP 8.4 `php -l`; 69 PHP files lint clean |
| EDIT-01 | POST tampered product code/revision/manufacturing/status to generic edit page | Identity/workflow values stay unchanged | PASS — edit page ignores identity/status fields |

## Remaining release checks

- Have a teammate independently rerun the matrix on a disposable copy and review the migration diff.
- Add an explicit duplicate Test ID / duplicate numeric product-code / duplicate numeric test-code POST regression; DB uniqueness constraints already exist.
- Run every acceptance case on the shared demo database after the owner creates Aiven, applies migrations, and loads demo seed.
- Confirm instructor/supervisor accepts the example ID split before real records are entered.
- Do not treat this matrix as production validation or laboratory certification.
