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

// Middleware is loaded after the session and CSRF helpers it calls are defined.
require_once dirname(__DIR__) . '/middlewares/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middlewares/RoleMiddleware.php';
require_once dirname(__DIR__) . '/middlewares/CsrfMiddleware.php';

/**
 * Keep the existing page API while delegating access checks to middleware.
 */
function require_login(): void
{
    AuthMiddleware::handle();
}

/**
 * Restrict a page to one or more named roles through role middleware.
 */
function require_roles(array $allowedRoles): void
{
    RoleMiddleware::handle($allowedRoles);
}

/**
 * Load the shared route-role map used by the navigation and endpoint guards.
 */
function app_route_permissions(): array
{
    static $permissions = null;
    if ($permissions === null) {
        $permissions = require __DIR__ . '/permissions.php';
    }

    return $permissions;
}

/**
 * Check whether the signed-in role may open a route without exposing denied links.
 */
function app_can_access_route(string $route, ?string $role = null): bool
{
    $route = basename($route);
    $role ??= (string) ($_SESSION['role'] ?? '');
    $allowedRoles = app_route_permissions()[$route] ?? [];

    return $role !== '' && in_array($role, $allowedRoles, true);
}

/**
 * Guard a page using the same role matrix that controls its sidebar link.
 */
function require_page_access(string $route): void
{
    AuthMiddleware::handle();
    $route = basename($route);
    $allowedRoles = app_route_permissions()[$route] ?? [];
    RoleMiddleware::handle($allowedRoles);
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
