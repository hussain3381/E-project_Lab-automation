<?php
// Friendly, styled deny page; the endpoint still returns an authoritative HTTP 403.
http_response_code(403);
$deniedRole = htmlspecialchars((string) ($_SESSION['role'] ?? 'Signed-in user'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access restricted | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
</head>
<body class="public-body">
    <main class="error-page">
        <div class="error-card" data-reveal>
            <span class="error-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
            <p class="eyebrow">ACCESS CONTROL</p>
            <h1>This area is restricted</h1>
            <p>Your <strong><?php echo $deniedRole; ?></strong> role does not have access to this page. Use a link available in your workspace or contact an administrator.</p>
            <div class="error-actions">
                <a class="button button-primary" href="dashboard.php"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Go to my dashboard</a>
                <a class="button button-quiet" href="logout.php">Sign out</a>
            </div>
        </div>
    </main>
</body>
</html>
