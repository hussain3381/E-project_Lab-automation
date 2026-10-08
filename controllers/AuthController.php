<?php
// Handle login requests separately from their HTML presentation.

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/models/User.php';

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

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        return 'Enter both your username and password.';
    }

    $user = User::findByUsername($connection, $username);
    if ($user === null || (int) $user['is_active'] !== 1 || !password_verify($password, (string) $user['password'])) {
        return 'Username or password is incorrect.';
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    csrf_token();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = (string) $user['name'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_activity'] = time();

    header('Location: dashboard.php', true, 303);
    exit;
}
