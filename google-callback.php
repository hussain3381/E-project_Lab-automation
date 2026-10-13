<?php
// Google redirects to this allowlisted route; identity is resolved only from Google's verified UserInfo.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/GoogleAuthController.php';

app_start_session();
try {
    $connection = Database::connection();
    $user = google_oauth_complete($connection);
    auth_establish_session($connection, $user, 'google');
    header('Location: dashboard.php', true, 303);
    exit;
} catch (DomainException $exception) {
    $message = strtolower($exception->getMessage());
    $error = str_contains($message, 'cancelled') || str_contains($message, 'denied')
        ? 'cancelled'
        : (str_contains($message, 'not configured') ? 'not_configured' : 'failed');
    header('Location: login.php?oauth_error=' . $error, true, 303);
    exit;
} catch (Throwable $exception) {
    error_log('Google OAuth callback failed: ' . $exception->getMessage());
    header('Location: login.php?oauth_error=failed', true, 303);
    exit;
}
