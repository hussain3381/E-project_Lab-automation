<?php
// Record a logout when possible, then end the authenticated session.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';

app_start_session();
if (!empty($_SESSION['user_id'])) {
    try {
        audit_write(Database::connection(), 'logout_succeeded', 'Session', null, 'User signed out.');
    } catch (Throwable $exception) {
        error_log('Lab Automation logout audit could not be written: ' . $exception->getMessage());
    }
}

app_logout_session();
header('Location: login.php', true, 303);
exit;
