<?php
// Route login requests through the MVC controller, then render the existing login design.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/google.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/AuthController.php';

app_start_session();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$login_error = null;
$oauthError = (string) ($_GET['oauth_error'] ?? '');
$oauthErrors = [
    'not_configured' => 'Google sign-in is not configured yet. The Administrator can add the OAuth Client ID, secret, and callback URL to the private .env file.',
    'cancelled' => 'Google sign-in was cancelled. You can try again or use your username and password.',
    'failed' => 'Google sign-in could not be completed. Please try again or use your username and password.',
];
if (isset($_GET['disabled'])) {
    $login_error = 'This account is inactive. Contact an Administrator for access.';
} elseif (isset($oauthErrors[$oauthError])) {
    $login_error = $oauthErrors[$oauthError];
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $login_error = auth_process_login(Database::connection());
    } catch (Throwable $exception) {
        error_log('Lab Automation login failed: ' . $exception->getMessage());
        $login_error = APP_DEBUG
            ? 'Database connection failed. Check the .env settings and make sure the database is running.'
            : 'Login is temporarily unavailable. Please try again later.';
    }
}

$csrfToken = csrf_token();
$googleAuthAvailable = google_oauth_is_configured();
require __DIR__ . '/views/auth/login.php';
