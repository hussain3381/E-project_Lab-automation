# Seed data

`001_demo.sql` contains disposable catalogue/sample records and four demonstration accounts. It links the demo Tester account to a tester profile so its dashboard and assigned-test list show useful records. All demonstration accounts use `LabDemo@123` through a password hash. Do not include real laboratory records or production credentials in seed files; change/remove demo accounts before a shared or production deployment.

Public self-registration always creates an active `Tester` account with a linked tester profile. Users cannot choose Manager, Quality Control, or Administrator; an Administrator must grant any elevated role from the protected user-management page.
