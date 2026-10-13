# Tests

- `SRS-ACCEPTANCE-MATRIX.md` records the tested SRS/role/public flows, expected outcomes, and remaining release checks.
- PHP lint: `find . -name '*.php' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l`.
- Frontend checks: `npm run check:js && npm run build`.
- On a disposable MySQL/MariaDB database, run `php scripts/migrate.php` twice, then `php scripts/seed_demo.php`; verify the second migration run is a no-op.
- Do not run destructive cases against the shared Aiven service or real lab data. Keep each workflow test's input, expected result, actual result, and pass/fail outcome documented.
