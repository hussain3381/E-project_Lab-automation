<?php
// Centralize session, CSRF, and authenticated-page checks.

declare(strict_types=1);

require_once __DIR__ . '/app.php';

/**
 * Start a hardened PHP session exactly once per request.
 */
function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('LABAUTOMATIONSESSID');
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Create or return the CSRF token stored in the current session.
 */
function csrf_token(): string
{
    app_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/**
 * Check a submitted CSRF token without exposing the expected token.
 */
function csrf_is_valid(?string $submittedToken): bool
{
    app_start_session();
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $submittedToken);
}

/**
 * Redirect unauthenticated users to the login screen.
 */
function require_login(): void
{
    app_start_session();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php', true, 302);
        exit;
    }
}

/**
 * Restrict a page to one or more named roles.
 */
function require_roles(array $allowedRoles): void
{
    require_login();
    $userRole = (string) ($_SESSION['role'] ?? '');

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo 'You do not have permission to access this page.';
        exit;
    }
}

/**
 * Clear session data and expire the browser cookie on logout.
 */
function app_logout_session(): void
{
    app_start_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parameters['path'],
            'domain' => $parameters['domain'],
            'secure' => (bool) $parameters['secure'],
            'httponly' => (bool) $parameters['httponly'],
            'samesite' => $parameters['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
