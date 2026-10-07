<?php
// Public account creation can only create the least-privileged Tester role.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/RegistrationController.php';
app_start_session();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$registrationError = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $registrationError = auth_process_registration(Database::connection());
    } catch (Throwable $exception) {
        error_log('Lab Automation registration request failed: ' . $exception->getMessage());
        $registrationError = APP_DEBUG ? 'Account setup failed. Run the latest database migration and verify the database connection.' : 'Registration is temporarily unavailable.';
    }
}

$csrfToken = csrf_token();
require __DIR__ . '/views/auth/register.php';
