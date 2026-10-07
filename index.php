<?php
// Public landing is the entry point; signed-in staff go directly to their role workspace.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
app_start_session();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

require __DIR__ . '/views/public/home.php';
