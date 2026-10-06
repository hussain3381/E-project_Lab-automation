<?php
// Protect state-changing requests with a session-bound CSRF token.

declare(strict_types=1);

final class CsrfMiddleware
{
    public static function handlePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $submittedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null;
        if (!csrf_is_valid($submittedToken)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            exit('The form expired or failed its security check. Refresh the page and try again.');
        }
    }
}
