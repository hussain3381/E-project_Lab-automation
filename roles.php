<?php
// Read-only registry of application roles and their expected access boundaries.

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';

app_start_session();
require_roles(['Administrator']);

try {
    $conn = Database::connection();
    $result = $conn->query(
        'SELECT r.id, r.role_name, r.description, r.is_system, ' .
        'COUNT(u.id) AS user_count, ' .
        'COALESCE(SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_users ' .
        'FROM roles AS r ' .
        'LEFT JOIN users AS u ON u.role = r.role_name ' .
        'GROUP BY r.id, r.role_name, r.description, r.is_system ' .
        'ORDER BY r.id'
    );
    $roleRows = $result->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $exception) {
    error_log('Role registry page failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Role registry is unavailable. Apply the latest database migration and try again.');
}

$roleAccess = require __DIR__ . '/config/roles.php';
$currentName = (string) ($_SESSION['name'] ?? 'Administrator');
$currentRole = (string) ($_SESSION['role'] ?? 'Administrator');
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('lab-theme')||'dark';}catch(e){document.documentElement.dataset.theme='dark';}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Roles &amp; Access | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <link rel="stylesheet" href="assets/css/pages/roles.css">
    <script type="module" src="assets/compiled/app.js"></script>
</head>
<body class="roles-page">
    <aside class="roles-sidebar">
        <a class="roles-brand" href="dashboard.php"><span class="roles-brand-mark">⚡</span><span><strong>LAB AUTOMATION</strong><small>Electrical Testing</small></span></a>
        <p class="roles-nav-title">Main menu</p>
        <nav class="roles-nav" aria-label="Main navigation">
            <a href="dashboard.php">⌂ <span>Dashboard</span></a>
            <a href="products.php">▣ <span>Products</span></a>
            <a href="testing.php">⚗ <span>Testing</span></a>
            <a href="search.php">⌕ <span>Advanced search</span></a>
            <a href="reports.php">▤ <span>Reports</span></a>
            <p class="roles-nav-title">Administration</p>
            <a href="users.php">♟ <span>Users</span></a>
            <a class="is-active" href="roles.php" aria-current="page">♜ <span>Roles &amp; access</span></a>
            <a href="settings.php">⚙ <span>Settings</span></a>
            <a href="logout.php">↪ <span>Logout</span></a>
        </nav>
        <div class="roles-sidebar-user"><span class="roles-avatar"><?php echo $escape(strtoupper(substr($currentName, 0, 1))); ?></span><span><strong><?php echo $escape($currentName); ?></strong><small><?php echo $escape($currentRole); ?></small></span></div>
    </aside>

    <main class="roles-main">
        <header class="roles-header">
            <div>
                <p class="roles-eyebrow">SYSTEM ADMINISTRATION</p>
                <h1>Roles &amp; access</h1>
                <p class="roles-lead">Built-in roles control which laboratory tools each account can use.</p>
            </div>
            <a class="roles-primary-action" href="users.php">Manage users <span aria-hidden="true">→</span></a>
        </header>

        <section class="roles-notice" aria-label="Security note">
            <strong>Access is enforced in PHP middleware.</strong>
            <span>These role names are fixed system roles; the access descriptions below are informational and the route middleware remains the enforcement layer.</span>
        </section>

        <section class="roles-grid" aria-label="Registered roles">
            <?php foreach ($roleRows as $roleRow): ?>
                <?php $roleName = (string) $roleRow['role_name']; $details = $roleAccess[$roleName] ?? ['summary' => (string) $roleRow['description'], 'access' => ['No custom route permissions are configured.']]; ?>
                <article class="role-card">
                    <div class="role-card-heading">
                        <span class="role-icon" aria-hidden="true">♜</span>
                        <span class="role-system-badge">Built-in</span>
                    </div>
                    <h2><?php echo $escape($roleName); ?></h2>
                    <p class="role-description"><?php echo $escape($details['summary']); ?></p>
                    <div class="role-counts">
                        <span><strong><?php echo (int) $roleRow['user_count']; ?></strong> account<?php echo (int) $roleRow['user_count'] === 1 ? '' : 's'; ?></span>
                        <span><strong><?php echo (int) $roleRow['active_users']; ?></strong> active</span>
                    </div>
                    <h3>Typical access</h3>
                    <ul>
                        <?php foreach ($details['access'] as $accessItem): ?>
                            <li><?php echo $escape($accessItem); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </section>

        <footer class="roles-footer">
            <span>Need to assign a role?</span>
            <a href="users.php">Open User Management</a>
        </footer>
    </main>
</body>
</html>
