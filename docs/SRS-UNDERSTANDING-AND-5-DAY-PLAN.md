# Lab Automation System — SRS samajh aur 5-day team plan

## Project ka purpose

Electrical-appliance manufacturers ke lab mein switchgear, fuses, capacitors, resistors aur doosre products ki test records ko paper/Excel ke bajaye traceable application mein rakhna. Ye inventory/accounting application nahi; ye product identity, laboratory test records, department routing, results, remarks aur workflow status track karta hai.

## SRS requirements

1. Product types/categories ke hisaab se modular configuration.
2. Har product family ke liye required/optional test types; har test department ke hisaab se route ho.
3. Har product ka unique 10-digit Product ID, product code/model, revision aur manufacturing number se linked.
4. Har test ka unique 12-digit Test ID, Product ID prefix, test code aur roll se linked.
5. Test record mein criteria, expected/actual output, result, detailed remarks, date aur tester(s) hon.
6. Product ID ya Test ID se lookup ho; advanced filters bhi hon.
7. Pending/in-progress/pass/fail aur product ka agla step dikhna chahiye.
8. Failure ke baad product re-manufacture/retest route mein jaye. **Current cycle ke tamam required lab tests PASS** hon tabhi CPRI readiness/handoff allow ho.

### CPRI boundary

SRS CPRI API ya automatic data transfer specify nahi karta. Is liye app `CPRI Ready` ko gate karta hai aur manually date, reference, user, notes ke saath handoff record karta hai. CPRI ko koi API call nahi hoti; actual CPRI testing app ke bahar hai.

## ID split — sprint decision

User ne demo/eProject ke liye ye exact example split select kiya hai (final institutional rule samajh kar assume na karein; supervisor se verify karna ab bhi sensible hai):

- **Product ID (10 digits):** exact registered product code/model ka numeric mapping (2) + revision code (2) + manufacturing sequence (6).
- **Test ID (12 digits):** Product ID ke pehle 4 digits + test type numeric code (3) + per-product/per-test-type roll (5).
- Example: `SWG01` → model numeric code `01`; revision `01`; manufacturing `004417` → Product ID `0101004417`. Test type numeric code `001`, roll `00001` → Test ID `010100100001`.
- Mapping exact model/code ko identify karegi, sirf family ko nahi. Is liye `product_codes` registry use hoti hai. `product_types.numeric_code` family field Product ID generate karne ke liye use nahi hoti.
- Segments silently truncate nahi hote: invalid lengths reject hote hain; leading zeroes string ke taur par preserve hote hain; unique DB indexes + locked roll sequence collision rokta hai.
- Generated sequence reserve hone ke baad test insert fail ho to ek roll skip ho sakta hai, magar number reuse nahi hota.

## Current repo/branch status

Baseline GitHub repo dark-teal PHP screens, product/test forms, advanced search aur test-type page rakhta tha. Full app Laravel mein rewrite nahi ki gayi; root-level pages ko URLs bachate hue incremental tareeqe se MVC mein migrate kiya ja raha hai.

**Foundation pehle se implemented:** MVC/config ka starting structure, env-driven `mysqli`, login/password verification/session hardening, page guards, selected role limits, CSRF, POST-only deletes, CSS-token dark/light theme, Vite build, migrations, demo seed.

**Is sprint mein ab tak implemented:**

- Product ID generator + exact product/model numeric registry; Add Product DB mapping se ID banata hai aur no-truncation validations karta hai.
- Test type par separate 3-digit numeric ID code; concurrency-safe per-product/per-test-type roll generator, legacy ID collision/backfill checks.
- Product families, exact product-code mappings, departments aur product-family test plan ke admin screens.
- Test entry: criteria/expected/actual/result/date; multi-tester participants; department ID; test-cycle assignment.
- PASS/FAIL/pending state updates; failure → re-manufacture status → manual release for retest; all required tests current cycle mein pass hon to `CPRI Ready`; manual CPRI handoff audit without API integration.
- Clean install + idempotent migrate + legacy-shaped schema migration + demo seed checks pass huay. ID generator, concurrent roll allocation, product/test form, multi-tester, failure/rework/retest aur CPRI gate ka HTTP smoke test bhi pass hua.

**Abhi baqi / review:** edit-product aur purane CRUD pages ko generated ID policy se align karna; advanced search/list/detail mein participants, department, cycle aur CPRI data achhi tarah dikhana; workflow race/error/role tests; broader MVC migration; Aiven service abhi create/connect nahi hua. Production certification scope mein nahi.

## 5 din ke tasks — clear ownership

### Member A — PHP/backend lead (project owner; strongest PHP)

- **Din 1:** Git branch/repo audit, config + shared DB baseline, migration review; instructor se ID split verify.
- **Din 2:** Auth/session/CSRF/role checks; Product/Test ID generation aur uniqueness ka ownership; code review.
- **Din 3:** Product/test persistence, product-family test-plan mapping, tester(s)/department data; failure/rework/retest transactions.
- **Din 4:** All-required-tests-pass → CPRI-ready/manual handoff gate, advanced search integration, DB migration/rollback checks.
- **Din 5:** End-to-end regression, access-control/security review, Aiven demo DB setup, release checklist (GitHub push only after team review).

### Member B — Data, modules aur functional QA (basic/moderate PHP)

- **Din 1:** SRS traceability sheet, field dictionary, realistic samples aur 12–15 acceptance cases.
- **Din 2:** Product family, exact product-code, test-type, department aur family-test-plan screens verify; seed/demo data check.
- **Din 3:** Test-entry criteria/expected/actual/remarks/tester(s) fields, lists/detail screens aur input validation test.
- **Din 4:** PASS/FAIL/PENDING, different departments, duplicate IDs, fail→re-manufacture→retest, incomplete CPRI gate chala kar bug list.
- **Din 5:** Full acceptance matrix rerun, demo reset/seed, QA sign-off aur reproducible bug notes.

### Member C — Frontend aur documentation (basic/moderate PHP)

- **Din 1:** Existing pages/assets inventory, color-literal audit, theme toggle and dark/light baseline check.
- **Din 2:** Dark/light parity, responsive tables/forms, navigation and button/alert polish.
- **Din 3:** Advanced-search filters, validation/error/success states, status badges, workflow progress and detail-page UI.
- **Din 4:** Keyboard/form-label/contrast checks, Chrome/Edge/mobile visual testing; screenshots + UI bug list.
- **Din 5:** Setup/user guide, screenshots, demo checklist, final ZIP/repo check; `.env`, passwords, certificates share nahi karne.

## Five-day acceptance gate

- Login ke baghair private pages nahi khulte; protected POST bina CSRF fail hota hai.
- Product save par mapping + revision + manufacturing sequence se unique 10-digit ID banta hai; wrong lengths truncate nahi hote.
- Test save par unique 12-digit ID; concurrent same product/test-type submissions ko alag roll milta hai.
- Criteria, expected/actual, result, remarks, date, department aur multiple testers save/retrieve hote hain.
- Test plan ke tamam required tests current cycle mein PASS hon tabhi CPRI handoff action chalta hai.
- FAIL se re-manufacture status lagta hai; rework release ke baad next cycle mein retest hota hai; previous history delete nahi hoti.
- Product ID aur Test ID se search, plus advanced filters, kaam karte hain.
- Dark/light choice pages ke darmiyan persist hoti hai; page CSS shared variables use karti hai.
- Shared DB par migrations one-time/idempotent hon; developers private `.env` se same remote DB ko use karen; credentials Git/chat mein nahi.

Five-day goal demonstrable eProject v1 hai. Aiven Free sirf demo/coursework ke liye; real laboratory ke liye backups/restore, high availability, TLS/network restrictions, security review aur institutional ID approval alag se lazmi hain.
