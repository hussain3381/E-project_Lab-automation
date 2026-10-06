# Shared MySQL database — team setup

## Current status

No hosting account was supplied, so the repository is prepared for a remote MySQL host but is not yet connected to a public/shared database. The MariaDB instance used for our checks is sandbox-local and cannot be reached from the team's PCs.

## Recommended route for the five-day class/demo build

Aiven currently lists MySQL in its Free tier; its official pricing page says free services can be used without a credit card and continue free of charge, subject to the plan's limits. Aiven also reserves the right to power off free services that are inactive, so use a free database only for coursework/demo data—not as the final production database for a real laboratory.

1. One team member (the project owner) creates the MySQL service and privately saves the connection details: hostname, port, database name, username, password, and the provider CA certificate if TLS verification is configured.
2. Each developer creates a private local `.env` from `.env.example`. Every `.env` points to that same MySQL service. Passwords and certificates stay out of GitHub and chat.
3. The project owner runs `php scripts/migrate.php` once after the remote database has been created. That creates/updates tables and records applied migration filenames. Teammates do not import separate SQL dumps on their laptops.
4. Only on a throwaway demo database, the owner may run `php scripts/seed_demo.php`. Do not load demo seed records into a real production database.
5. Each developer runs the PHP app locally; their app requests reach the same remote MySQL host. No PHP server in this AI workspace is used as the team's database server.

## Security rules

- Do not use a database administrator/root account as the application's permanent account. Use a restricted application user; use a separate migration-capable user only when schema changes are needed.
- Keep TLS CA verification enabled for remote connections where the provider supplies a CA certificate. This repository supports `DB_SSL_CA`.
- Limit who can connect to the database. Use provider network controls or a VPN when available; do not expose an unrestricted MySQL port.
- Keep backups and run a restore test before any real data is entered.
- Do not put real customer/product records in the demo DB.

## Aiven documentation

- Official service pricing: https://aiven.io/docs/platform/concepts/service-pricing
- Official PHP/MySQL connection guidance: https://aiven.io/docs/products/mysql/howto/connect-with-php

The team still needs to create the account/service and provide non-secret connection metadata. Never send the password or an API token in this conversation; put it only in each developer's private `.env` file.
