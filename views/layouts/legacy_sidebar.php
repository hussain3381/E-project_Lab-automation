<?php
// Compatibility sidebar for pages not yet migrated to the modern app shell.
// Navigation remains role-aware while legacy routes keep their existing URLs and page CSS.
$legacyActivePage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$legacyGroups = [
    ['label' => 'Workspace', 'items' => [
        ['route' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
        ['route' => 'products.php', 'label' => 'Products', 'icon' => 'fa-cubes-stacked'],
        ['route' => 'testing.php', 'label' => 'Testing records', 'icon' => 'fa-flask-vial'],
        ['route' => 'testing-status.php', 'label' => 'Test status', 'icon' => 'fa-chart-simple'],
        ['route' => 'search.php', 'label' => 'Search', 'icon' => 'fa-magnifying-glass'],
        ['route' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
    ]],
    ['label' => 'Lab setup', 'items' => [
        ['route' => 'test-types.php', 'label' => 'Test types', 'icon' => 'fa-list-check'],
        ['route' => 'testers.php', 'label' => 'Testers', 'icon' => 'fa-user-group'],
        ['route' => 'departments.php', 'label' => 'Departments', 'icon' => 'fa-building'],
        ['route' => 'product-catalog.php', 'label' => 'Product catalog', 'icon' => 'fa-boxes-stacked'],
        ['route' => 'product-test-plan.php', 'label' => 'Family test plans', 'icon' => 'fa-diagram-project'],
        ['route' => 'product-workflow.php', 'label' => 'Product workflow', 'icon' => 'fa-arrows-spin'],
        ['route' => 'settings.php', 'label' => 'Settings', 'icon' => 'fa-sliders'],
        ['route' => 'users.php', 'label' => 'User accounts', 'icon' => 'fa-users-gear'],
        ['route' => 'roles.php', 'label' => 'Roles & access', 'icon' => 'fa-shield-halved'],
    ]],
];
$legacyName = (string) ($_SESSION['name'] ?? 'Lab staff');
$legacyRole = (string) ($_SESSION['role'] ?? 'Staff');
$legacyParts = preg_split('/\s+/', trim($legacyName)) ?: [];
$legacyInitials = '';
foreach ($legacyParts as $legacyPart) {
    if ($legacyPart !== '') {
        $legacyInitials .= strtoupper(substr($legacyPart, 0, 1));
    }
    if (strlen($legacyInitials) >= 2) {
        break;
    }
}
$legacyInitials = $legacyInitials !== '' ? $legacyInitials : 'LA';
?>
<aside class="sidebar app-legacy-sidebar">
    <a class="brand" href="dashboard.php">
        <span class="brand-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
        <span class="brand-copy"><strong>LAB AUTOMATION</strong><small>Electrical testing workspace</small></span>
    </a>
    <?php foreach ($legacyGroups as $legacyGroup): ?>
        <?php $visibleLinks = array_values(array_filter($legacyGroup['items'], static fn (array $item): bool => app_can_access_route($item['route']))); ?>
        <?php if ($visibleLinks !== []): ?>
            <div class="nav-title"><?php echo htmlspecialchars($legacyGroup['label'], ENT_QUOTES, 'UTF-8'); ?></div>
            <nav class="nav" aria-label="<?php echo htmlspecialchars($legacyGroup['label'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach ($visibleLinks as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['route'], ENT_QUOTES, 'UTF-8'); ?>" class="nav-link<?php echo $legacyActivePage === $item['route'] ? ' active' : ''; ?>"<?php echo $legacyActivePage === $item['route'] ? ' aria-current="page"' : ''; ?>>
                        <span class="nav-icon"><i class="fa-solid <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="sidebar-bottom">
        <div class="user-box">
            <div class="user-avatar"><?php echo htmlspecialchars($legacyInitials, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="user-info"><strong><?php echo htmlspecialchars($legacyName, ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($legacyRole, ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
        <a class="nav-link" href="logout.php"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span><span>Sign out</span></a>
    </div>
</aside>
