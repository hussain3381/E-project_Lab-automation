<?php
// Compatibility entry point for the existing pages while the MVC migration continues.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';

app_start_session();

// Run request middleware before opening the database for protected legacy pages.
AuthMiddleware::handle();
CsrfMiddleware::handlePost();

try {
    // Existing pages expect this mysqli variable; new models use Database::connection().
    $conn = Database::connection();
} catch (Throwable $exception) {
    http_response_code(500);
    $publicMessage = APP_DEBUG
        ? htmlspecialchars($exception->getPrevious()?->getMessage() ?? $exception->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'The database is temporarily unavailable. Please contact the administrator.';
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Database unavailable</title><body><h1>Database unavailable</h1><p>' . $publicMessage . '</p></body></html>';
    exit;
}

// Re-check role and active state on every authenticated request so admin changes take effect immediately.
$accountCheck = $conn->prepare('SELECT id, name, username, email, role, is_active FROM users WHERE id = ? LIMIT 1');
$accountId = (int) ($_SESSION['user_id'] ?? 0);
$accountCheck->bind_param('i', $accountId);
$accountCheck->execute();
$account = $accountCheck->get_result()->fetch_assoc();
$accountCheck->close();

if (!$account || (int) $account['is_active'] !== 1) {
    app_logout_session();
    header('Location: login.php?disabled=1', true, 303);
    exit;
}

$_SESSION['name'] = (string) $account['name'];
$_SESSION['username'] = (string) $account['username'];
$_SESSION['email'] = (string) ($account['email'] ?? '');
$_SESSION['role'] = (string) $account['role'];
