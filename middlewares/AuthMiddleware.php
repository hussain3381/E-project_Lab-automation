<?php
// Shared authentication boundary for the plain-PHP application.

declare(strict_types=1);

/**
 * Redirect anonymous visitors before a protected page loads database data.
 */
final class AuthMiddleware
{
    public static function handle(): void
    {
        app_start_session();

        if (empty($_SESSION['user_id'])) {
            header('Location: login.php', true, 302);
            exit;
        }
    }
}
