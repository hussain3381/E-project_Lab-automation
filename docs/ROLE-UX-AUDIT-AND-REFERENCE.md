# Lab Automation — Role aur UI/UX audit

**Date:** 12 October 2026  
**Scope:** Four roles, shared navigation, start-here guides, assigned test flow, rework, CPRI outcome, and market release.  
**Verification note:** Approved workflow changes, the assignment policy, per-user profile/preferences, and additive migration `010_user_profiles_and_preferences.sql` are implemented in source. JS checks, Vite build, and PHP grammar parsing passed on 12 October 2026. Native PHP, MySQL/MariaDB, and browser screenshot checks were unavailable; database execution, HTTP role tests, and visual acceptance remain pending. No database was changed.

## 1. Approved role-to-page map

`config/permissions.php` server-side access ko check karti hai. Sidebar par link hide hona security ka substitute nahi; protected page ka server guard bhi active rehna chahiye.

| Role | Daily tabs / actions | Restricted areas |
|---|---|---|
| **Administrator** | Overview, Products, Tests and results, Status overview, Search, Reports, full workflow; Add product and Assign a test | Existing full access retained: catalogue/test-plan setup, departments, test types, testers, settings, accounts, roles and activity log |
| **Lab Manager** | Overview, Products, Tests and results, Status overview, Search, Reports; register products, assign pending tests to one or more available Testers, edit open pending-test instructions, and open My profile | No rework/CPRI workflow actions, user/role admin, or admin-only catalogue/test-plan setup |
| **Tester** | Overview, My assigned tests, My assigned products, Find an assigned record; complete own assigned pending tests and open My profile | Cannot assign tests, view global reports/status, or manage workflow/setup |
| **Quality Control** | Overview, Review test status, Test records, Products, Search, Reports, Rework/CPRI workflow; assign pending tests/retests and open My profile | Owns rework completion, manual CPRI handoff/result, and market release; no lab setup/accounts |

## 2. Implemented workflow behavior (source-level; not runtime-verified)

- Administrator, Lab Manager, and QC can assign a pending current-cycle test to one or more available Testers; Testers cannot open `new-test.php`. The form uses a searchable, scrollable checkbox picker and revalidates tester availability inside the transaction.
- An open current-cycle test blocks any further assignment for that product. After it closes, only mapped test types not yet recorded in that cycle are eligible. Archived/closed products, inactive tests, and busy/inactive testers are unavailable.
- Lab Managers may revise date, criteria, expected output, and notes only while an assignment is pending in the active cycle. Result/measurement correction remains Administrator-only; changes are audited.
- `complete-test.php` accepts only the signed-in Tester’s pending assignment and rejects assignments from an earlier cycle. Administrator keeps existing direct result-entry and full workflow access.
- Quality Control and Administrator can mark re-manufacture complete, but only from the `Failed - Re-manufacturing` state. The product enters the next cycle; progress checks require **every required test** in that new cycle to pass.
- QC/Admin manually record CPRI result after handoff. PASS changes the product to `CPRI Approved`; a separate action records `Released to Market` only after PASS.
- CPRI FAIL follows the approved failure path: `Failed - Re-manufacturing`; QC confirms physical work, then an authorized assigner schedules eligible tests in the new cycle. The original handoff/result remains in history.
- Migration `009` adds CPRI result and market-release fields without deleting or replacing handoff rows. Migration `010` adds account phone details and a separate per-user theme/compact/reduced-motion preferences table; tester phone values backfill only into empty account phone fields. Existing CPRI outcomes default to `PENDING` until staff records them.
- No CPRI API or automatic external transfer is configured.

## 3. Remaining bugs / missing features / release checks

1. **Custom test-plan ordering:** test-plan order exists, but the UI still does not provide clear drag/up/down reordering. The Manager chooses the next test manually; the app does not enforce a department-by-department sequence.
2. **Product/Test ID segments:** the current 2+2+6 and 4+3+5 splits remain sprint choices, not verified institutional rules. Confirm before entering real lab data.
3. **Runtime regression is pending:** route 403s, migrations 009–010 and repeated manual upgrade on a backed-up database copy, sequential/busy-tester concurrency, Manager edit boundaries, CPRI PASS/FAIL, and release gating need local PHP/MySQL verification.
4. **Personal preferences:** test Profile save for each role, theme toggle persistence across devices, compact layout, reduced motion, and Admin-only lab-wide Settings.
5. **Visual review:** compare dashboard, assignment picker, status, reports, login/register, and catalogue in light/dark at desktop/mobile; browser screenshots were not available here.
6. **No production/certification claim:** no live database, external CPRI, laboratory equipment, or production deployment was tested here.

## 4. Simple UI reference

### Tester — assigned work only

```text
MY ASSIGNED TESTS       OPEN: 4       COMPLETED: 12

1. Open a pending assignment given by an authorized assigner
2. Read criteria and expected output
3. Enter actual output, choose PASS/FAIL, add remarks

[Record result]
```

### Lab Manager — intake and assignment

```text
Register product -> Choose an unused mapped test -> Assign available Tester(s) -> Monitor progress

PRODUCT 0101004417     CYCLE 1     OPEN TEST: none
Next: assign only after the previous current-cycle test closes
[Assign pending test] [View status]
```

### Quality Control — rework and final external handoff

```text
FAILED - RE-MANUFACTURING -> Confirm physical rework
CPRI READY -> Record manual handoff
HANDED TO CPRI -> Enter returned PASS/FAIL
CPRI APPROVED -> Record market release

CPRI FAIL -> Re-manufacture -> next cycle repeats every required test
```

### Status guide

- **CPRI Ready:** every required internal test passed in the current cycle; not CPRI approval.
- **Handed to CPRI:** dispatch/handoff was recorded; external result is still pending.
- **CPRI Approved:** manual CPRI PASS is recorded; market release is still a separate action.
- **Released to Market:** QC/Admin recorded the final local release.
- **CPRI rejection:** stored with the original handoff and routed to re-manufacture.

## 5. Local verification checklist

On a **backed-up disposable copy**, apply the additive SQL or run migrations; do not import the demo dump into the user's existing database.

- Sign in as each role: Admin/Manager/QC can open assignment; Tester gets 403 on `new-test.php`; Manager edits only open pending assignment details; Tester cannot open global reports/status; lab-wide Settings stays Admin-only.
- Admin, Manager, or QC creates a pending assignment for the current cycle. Attempt a second assignment before closing it, repeat a mapped test type, select a busy tester, and tamper with POST values; server rejects each invalid case. Once it closes, only unused mapped test types become eligible.
- Tester A can complete their pending assignment; another Tester, a non-pending record, a previous-cycle assignment, or an unassigned product is rejected.
- Fail one required lab test: product routes to re-manufacture. Confirm rework, then reassign **all** required tests in cycle 2. Old cycle 1 results must not satisfy cycle 2.
- Pass all required tests in the cycle: CPRI handoff is allowed. Incomplete current-cycle tests must block handoff.
- Record CPRI PASS only after handoff; verify `CPRI Approved`, then verify release can be recorded once and only once.
- Record CPRI FAIL only after handoff; verify `Failed - Re-manufacturing`, retained handoff/result history, QC rework completion, and a full new required-test cycle.
- Verify actor/date/reference/notes on product workflow and product details; verify no API call is attempted.
- Review desktop/mobile, keyboard/focus, and dark/light screenshots for dashboard, assignment form, workflow table, and product details.
