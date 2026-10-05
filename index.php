<?php
// Send visitors to the correct entry page; the existing screen URLs remain compatible.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
app_start_session();

$destination = !empty($_SESSION['user_id']) ? 'dashboard.php' : 'login.php';
header('Location: ' . $destination, true, 302);
exit;
