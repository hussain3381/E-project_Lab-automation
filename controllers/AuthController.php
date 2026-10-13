<?php
// Handle local login and shared session establishment separately from HTML presentation.
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/models/User.php';

/**
 * Store one authenticated identity using the same session policy for password and Google login.
 */
function auth_establish_session(mysqli $connection, array $user, string $method): void
{
    app_start_session();
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    csrf_token();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = (string) $user['name'];
    $_SESSION['username'] = (string) ($user['username'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_activity'] = time();

    // A missing audit table should not strand a valid user before migrations are applied.
    try {
        audit_write($connection, 'login_succeeded', 'Session', null, 'User signed in successfully.', [
            'method' => $method,
        ]);
    } catch (Throwable $exception) {
        error_log('Lab Automation login audit could not be written: ' . $exception->getMessage());
    }
}

/**
 * Authenticate one POST request and return a safe error message when it fails.
 */
function auth_process_login(mysqli $connection): ?string
{
    app_start_session();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return null;
    }

    if (!csrf_is_valid(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        return 'Your form expired. Refresh the page and try again.';
    }

    $loginIdentity = trim((string) ($_POST['login_identity'] ?? $_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginIdentity === '' || $password === '') {
        return 'Enter your username or email and password.';
    }

    $user = User::findByLoginIdentity($connection, $loginIdentity);
    if ($user === null || (int) $user['is_active'] !== 1 || !password_verify($password, (string) $user['password'])) {
        try {
            audit_write($connection, 'login_failed', 'Session', null, 'Password authentication was rejected.');
        } catch (Throwable $exception) {
            error_log('Lab Automation failed-login audit could not be written: ' . $exception->getMessage());
        }
        return 'Username, email, or password is incorrect.';
    }

    auth_establish_session($connection, $user, 'password');
    header('Location: dashboard.php', true, 303);
    exit;
}
