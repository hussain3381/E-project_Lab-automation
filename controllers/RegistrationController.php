<?php
// Validate public registration and always create the least-privileged Tester role.
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/models/User.php';

function auth_process_registration(mysqli $connection): ?string
{
    app_start_session();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return null;
    }

    if (!csrf_is_valid(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        return 'Your form expired. Refresh the page and try again.';
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($name === '' || strlen($name) > 120) {
        return 'Enter your name (maximum 120 characters).';
    }
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
        return 'Username must be 3–40 characters and use letters, numbers, dots, underscores, or hyphens.';
    }
    if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }
    if (strlen($password) < 12) {
        return 'Use a password with at least 12 characters.';
    }
    if (!hash_equals($password, $passwordConfirm)) {
        return 'The passwords do not match.';
    }

    try {
        $user = User::createTesterAccount($connection, $name, $username, $email, $password);
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        csrf_token();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'Tester';
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();

        header('Location: dashboard.php', true, 303);
        exit;
    } catch (DomainException $exception) {
        return $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        error_log('Lab Automation registration failed: ' . $exception->getMessage());
        if ((int) $exception->getCode() === 1062) {
            return 'That username or email is already registered.';
        }
        return APP_DEBUG ? 'Account setup failed. Check the local database migration.' : 'Registration is temporarily unavailable.';
    } catch (Throwable $exception) {
        error_log('Lab Automation registration failed: ' . $exception->getMessage());
        return APP_DEBUG ? 'Account setup failed. Check the local database migration.' : 'Registration is temporarily unavailable.';
    }
}
