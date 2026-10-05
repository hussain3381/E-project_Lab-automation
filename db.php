<?php
// Compatibility entry point for the existing pages while the MVC migration continues.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';

app_start_session();

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

// All legacy pages that include db.php are private application pages.
require_login();

// Reject every unsafe POST request unless it carries the session's CSRF token.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_is_valid(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
    http_response_code(419);
    exit('The form expired or failed its security check. Refresh the page and try again.');
}
