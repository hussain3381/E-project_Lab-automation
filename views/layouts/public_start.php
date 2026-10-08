<?php
// Common public-site shell used by the landing, about, contact, login, and register screens.
$publicTitle = $publicTitle ?? 'Lab Automation';
$publicActivePage = basename($publicActivePage ?? ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="var(--theme-bg)">
    <title><?php echo htmlspecialchars($publicTitle, ENT_QUOTES, 'UTF-8'); ?> | Lab Automation</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('lab-theme')||'dark';}catch(e){document.documentElement.dataset.theme='dark';}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
</head>
<body class="public-body">
<header class="public-nav">
    <a class="app-brand" href="index.php" aria-label="Lab Automation home">
        <span class="app-brand-mark"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
        <span>
            <span class="app-brand-name">LAB AUTOMATION</span>
            <span class="app-brand-caption">Electrical testing system</span>
        </span>
    </a>
    <nav class="public-nav-links" aria-label="Public navigation">
        <a href="index.php"<?php echo $publicActivePage === 'index.php' ? ' aria-current="page"' : ''; ?>>Home</a>
        <a href="about.php"<?php echo $publicActivePage === 'about.php' ? ' aria-current="page"' : ''; ?>>About</a>
        <a href="contact.php"<?php echo $publicActivePage === 'contact.php' ? ' aria-current="page"' : ''; ?>>Contact</a>
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch theme" aria-pressed="false"></button>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a class="button button-primary" href="dashboard.php"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Workspace</a>
        <?php else: ?>
            <a href="login.php">Sign in</a>
            <a class="button button-primary" href="register.php">Create account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="public-main">
