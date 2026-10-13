<?php
// Begin the Google OAuth flow. Provider values are never accepted from the browser.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/controllers/GoogleAuthController.php';

app_start_session();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 303);
    exit;
}

try {
    google_oauth_begin();
} catch (DomainException $exception) {
    header('Location: login.php?oauth_error=not_configured', true, 303);
    exit;
} catch (Throwable $exception) {
    error_log('Google OAuth start failed: ' . $exception->getMessage());
    header('Location: login.php?oauth_error=failed', true, 303);
    exit;
}
