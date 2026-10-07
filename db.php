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
