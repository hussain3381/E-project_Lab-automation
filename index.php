<?php
// Keep the public landing page available to signed-in and signed-out visitors.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
app_start_session();

require __DIR__ . '/views/public/home.php';
