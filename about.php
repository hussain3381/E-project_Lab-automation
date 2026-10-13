<?php
// Public overview of the lab workflow and its access boundaries.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
app_start_session();
require __DIR__ . '/views/public/about.php';
