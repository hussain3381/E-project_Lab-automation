<?php
// Shared authenticated shell for the migrated workspace pages.
$pageTitle = $pageTitle ?? 'Workspace';
$pageDescription = $pageDescription ?? '';
$pageEyebrow = $pageEyebrow ?? 'LAB AUTOMATION';
$activePage = basename($activePage ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
$currentRole = (string) ($_SESSION['role'] ?? 'Staff');
$currentName = (string) ($_SESSION['name'] ?? 'Lab staff');
require_once dirname(__DIR__, 2) . '/models/UserPreferences.php';
$currentPreferences = UserPreferences::find($conn, (int) ($_SESSION['user_id'] ?? 0));
$currentTheme = in_array($currentPreferences['theme'], ['dark', 'light'], true) ? $currentPreferences['theme'] : 'dark';
$nameParts = preg_split('/\s+/', trim($currentName)) ?: [];
$avatarInitials = '';
foreach ($nameParts as $part) {
    if ($part !== '') {
        $avatarInitials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($avatarInitials) >= 2) {
        break;
    }
}
$avatarInitials = $avatarInitials !== '' ? $avatarInitials : 'LA';
?>
<!doctype html>
<html lang="en" data-theme="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>" data-user-theme="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>" data-reduced-motion="<?php echo $currentPreferences['reduced_motion'] ? '1' : '0'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="var(--theme-bg)">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <?php if (!empty($pageStylesheet)): ?><link rel="stylesheet" href="<?php echo htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <script type="module" src="assets/compiled/app.js"></script>
</head>
<body class="app-body<?php echo $currentPreferences['compact_dashboard'] ? ' is-compact' : ''; ?>">
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <button class="sidebar-backdrop" type="button" aria-label="Close navigation" data-sidebar-backdrop></button>
    <div class="app-main-wrap">
        <header class="app-topbar">
            <div class="app-topbar-left">
                <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-sidebar-toggle>
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div class="app-context"><span>Workspace</span><span aria-hidden="true"> / </span><strong><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></strong></div>
            </div>
            <div class="app-topbar-actions">
                <span class="app-topbar-role"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><?php echo htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch theme" aria-pressed="false"></button>
                <a class="button button-quiet" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span class="hidden sm:inline">Sign out</span></a>
            </div>
        </header>
        <main id="main-content" class="app-main">
            <div class="app-page-heading" data-reveal>
                <div>
                    <p class="eyebrow"><?php echo htmlspecialchars($pageEyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($pageDescription !== ''): ?>
                        <p><?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($pageActionHtml)): ?>
                    <div class="app-page-actions"><?php echo $pageActionHtml; ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($pageSuccess)): ?>
                <div class="alert alert-success" role="status"><?php echo htmlspecialchars($pageSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($pageError)): ?>
                <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
