<?php
// Compatibility shell for pages that still use their original content markup.
// The same navigation, workspace bar, theme control, and role policy appear on every signed-in page.
$legacyActivePage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$legacyPageTitles = [
    'dashboard.php' => 'Overview',
    'products.php' => 'Products',
    'testing.php' => 'Testing records',
    'testing-status.php' => 'Test status',
    'search.php' => 'Advanced Search',
    'reports.php' => 'Laboratory Reports',
    'test-types.php' => 'Test Types',
    'testers.php' => 'Testers Management',
    'departments.php' => 'Departments',
    'product-catalog.php' => 'Product Catalog',
    'product-test-plan.php' => 'Family Test Plans',
    'product-workflow.php' => 'Product Workflow',
    'settings.php' => 'System Settings',
    'users.php' => 'User accounts',
    'roles.php' => 'Roles & access',
    'admin-activity.php' => 'Activity log',
    'add-product.php' => 'Add Product',
    'edit-product.php' => 'Edit Product',
    'product-details.php' => 'Product Details',
    'new-test.php' => 'Record a Test',
    'test-details.php' => 'Test Details',
];
$legacyPageTitle = $legacyPageTitles[$legacyActivePage] ?? 'Workspace';
$legacyName = (string) ($_SESSION['name'] ?? 'Lab staff');
$legacyRole = (string) ($_SESSION['role'] ?? 'Staff');
$legacyNavigationConfig = require dirname(__DIR__, 2) . '/config/navigation.php';
$legacyRoleNavigation = $legacyNavigationConfig[$legacyRole] ?? $legacyNavigationConfig['Lab Manager'];
require_once dirname(__DIR__, 2) . '/models/UserPreferences.php';
$legacyPreferences = UserPreferences::find($conn, (int) ($_SESSION['user_id'] ?? 0));
$legacyTheme = in_array($legacyPreferences['theme'], ['dark', 'light'], true) ? $legacyPreferences['theme'] : 'dark';
$legacyGroups = $legacyRoleNavigation['groups'];
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
<script>
    document.documentElement.dataset.userTheme = <?php echo json_encode($legacyTheme, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.documentElement.dataset.theme = document.documentElement.dataset.userTheme;
    document.documentElement.dataset.reducedMotion = <?php echo json_encode($legacyPreferences['reduced_motion'] ? '1' : '0', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (document.body && <?php echo $legacyPreferences['compact_dashboard'] ? 'true' : 'false'; ?>) document.body.classList.add('is-compact');
</script>
<input type="hidden" data-theme-csrf value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
<button class="legacy-mobile-toggle" type="button" data-legacy-sidebar-toggle aria-label="Open navigation" aria-expanded="false">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
</button>
<button class="legacy-sidebar-backdrop" type="button" data-legacy-sidebar-backdrop aria-label="Close navigation"></button>
<aside class="sidebar app-legacy-sidebar" data-legacy-sidebar aria-label="Primary navigation">
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
        <?php $legacyPrimaryAction = $legacyRoleNavigation['primary'] ?? null; ?>
        <?php if (is_array($legacyPrimaryAction) && app_can_access_route($legacyPrimaryAction['route'])): ?>
            <a class="button button-primary w-full" href="<?php echo htmlspecialchars($legacyPrimaryAction['route'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa-solid <?php echo htmlspecialchars($legacyPrimaryAction['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($legacyPrimaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
        <?php $legacySecondaryAction = $legacyRoleNavigation['secondary'] ?? null; ?>
        <?php if (is_array($legacySecondaryAction) && app_can_access_route($legacySecondaryAction['route'])): ?>
            <a class="button button-quiet w-full" href="<?php echo htmlspecialchars($legacySecondaryAction['route'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa-solid <?php echo htmlspecialchars($legacySecondaryAction['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($legacySecondaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
        <div class="user-box">
            <div class="user-avatar"><?php echo htmlspecialchars($legacyInitials, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="user-info"><strong><?php echo htmlspecialchars($legacyName, ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($legacyRole, ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
    </div>
</aside>
<header class="app-topbar legacy-app-topbar">
    <div class="app-topbar-left">
        <div class="app-context"><span>Workspace</span><span aria-hidden="true"> / </span><strong><?php echo htmlspecialchars($legacyPageTitle, ENT_QUOTES, 'UTF-8'); ?></strong></div>
    </div>
    <div class="app-topbar-actions">
        <span class="app-topbar-role"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><?php echo htmlspecialchars($legacyRole, ENT_QUOTES, 'UTF-8'); ?></span>
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch theme" aria-pressed="false"></button>
        <a class="button button-quiet" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span class="hidden sm:inline">Sign out</span></a>
    </div>
</header>
