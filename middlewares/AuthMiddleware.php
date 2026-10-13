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

        // End abandoned sessions after a workday-length idle window.
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
        if ($lastActivity > 0 && time() - $lastActivity > 8 * 60 * 60) {
            app_logout_session();
            header('Location: login.php?expired=1', true, 303);
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
