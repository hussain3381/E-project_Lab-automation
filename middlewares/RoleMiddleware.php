<?php
// Enforce the role allow-list after the authentication middleware.

declare(strict_types=1);

final class RoleMiddleware
{
    /**
     * Require an authenticated session with one of the allowed role names.
     */
    public static function handle(array $allowedRoles): void
    {
        AuthMiddleware::handle();

        $userRole = (string) ($_SESSION['role'] ?? '');
        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            require APP_ROOT . '/views/errors/403.php';
            exit;
        }
    }
}
