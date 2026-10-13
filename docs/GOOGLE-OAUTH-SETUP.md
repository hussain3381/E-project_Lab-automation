# Optional Google sign-in setup

Google sign-in is implemented as an optional provider. Local username/password login and public Tester registration continue to work without Google credentials.

## 1. Prepare the local application

1. Run Apache and MySQL/MariaDB in XAMPP.
2. Make a private `.env` from `.env.example` if you do not already have one. Keep `.env` out of Git and do not share the client secret in chat.
3. Import `database/project_lab_db_import.sql` or run `php scripts/migrate.php` against the chosen local database. Migration 007 creates the audit table, Google-link column, and reversible product archive flag.
4. Confirm the project URL. If the directory is `C:\xampp\htdocs\LAB-AUTOMATION`, the callback URL is normally `http://localhost/LAB-AUTOMATION/google-callback.php`. Use your actual directory and port; the callback must match exactly.

## 2. Create a Google OAuth web client

1. In Google Cloud Console, configure the OAuth consent screen / Google Auth Platform for the project. While the consent screen is in testing mode, add the Google accounts that will test sign-in.
2. Create an OAuth client with application type **Web application**.
3. Add the exact local callback URL to **Authorized redirect URIs**. For example:
   `http://localhost/LAB-AUTOMATION/google-callback.php`
4. Copy the client ID and client secret into the private local `.env` file. Set the callback to the same URL:
   ```dotenv
   GOOGLE_CLIENT_ID=your-web-client-id
   GOOGLE_CLIENT_SECRET=your-web-client-secret
   GOOGLE_REDIRECT_URI=http://localhost/LAB-AUTOMATION/google-callback.php
   ```
5. Restart Apache if your environment configuration requires it, then refresh `login.php`. The Google button is enabled only when all three settings are valid.

For a hosted deployment, use the site's HTTPS callback URL and add that exact URL to Google's redirect allowlist. Do not use a real client secret in the repository's `.env.example`.

## Account behavior and safety

- Google identity is resolved server-side using OAuth authorization code + PKCE, one-time session state, and Google's HTTPS UserInfo endpoint. Only verified email addresses are accepted.
- A matching existing Lab Automation account is linked to the Google subject while preserving its existing role and active/inactive status. Inactive accounts remain blocked.
- A first-time Google identity gets a new Tester account and linked tester profile; it does not receive Administrator privileges.
- If multiple unlinked Tester profiles have the same Google email, sign-in stops and asks an Administrator to resolve the duplicate rather than attaching the wrong profile.
- Google tokens and secrets are never written to the audit log. The audit log records the sign-in method, account creation/linking, and subsequent sign-in event.
- If Google is not configured, the login page shows the option as unavailable and local sign-in remains usable.
