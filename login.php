<?php
// Route login requests through the MVC controller, then render the existing login design.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/AuthController.php';

app_start_session();

if (!empty($_SESSION['user_id']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: dashboard.php', true, 302);
    exit;
}

$login_error = null;
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
require __DIR__ . '/views/auth/login.php';
