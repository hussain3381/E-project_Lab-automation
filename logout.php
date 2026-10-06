<?php
// End the authenticated session and return to the login page.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';

app_logout_session();
header('Location: login.php', true, 303);
exit;
