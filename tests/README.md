# Tests

- `SRS-ACCEPTANCE-MATRIX.md` records the SRS scenarios, expected outcomes, sandbox observations, and remaining release checks.
- Run PHP syntax checks with `find . -name '*.php' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l`.
- Run `php scripts/migrate.php` twice on a disposable database to verify clean/idempotent installation; `php scripts/seed_demo.php` is demo-only.
- Never run destructive test cases against the shared Aiven service or real lab data. Keep each workflow test's input, expected result, actual result, and pass/fail outcome documented.
