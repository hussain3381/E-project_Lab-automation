<?php
// Render only routes allowed by the central role matrix.
$navigationGroups = [
    [
        'label' => 'Workspace',
        'items' => [
            ['route' => 'dashboard.php', 'label' => 'Overview', 'icon' => 'fa-gauge-high'],
            ['route' => 'products.php', 'label' => 'Products', 'icon' => 'fa-cubes-stacked'],
            ['route' => 'testing.php', 'label' => 'Testing records', 'icon' => 'fa-flask-vial'],
            ['route' => 'testing-status.php', 'label' => 'Test status', 'icon' => 'fa-chart-simple'],
            ['route' => 'search.php', 'label' => 'Search', 'icon' => 'fa-magnifying-glass'],
            ['route' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
        ],
    ],
    [
        'label' => 'Lab setup',
        'items' => [
            ['route' => 'test-types.php', 'label' => 'Test types', 'icon' => 'fa-list-check'],
            ['route' => 'testers.php', 'label' => 'Testers', 'icon' => 'fa-user-group'],
            ['route' => 'departments.php', 'label' => 'Departments', 'icon' => 'fa-building'],
            ['route' => 'product-catalog.php', 'label' => 'Product catalog', 'icon' => 'fa-boxes-stacked'],
            ['route' => 'product-test-plan.php', 'label' => 'Family test plans', 'icon' => 'fa-diagram-project'],
            ['route' => 'product-workflow.php', 'label' => 'Product workflow', 'icon' => 'fa-arrows-spin'],
            ['route' => 'settings.php', 'label' => 'Settings', 'icon' => 'fa-sliders'],
        ],
    ],
    [
        'label' => 'Access',
        'items' => [
            ['route' => 'users.php', 'label' => 'User accounts', 'icon' => 'fa-users-gear'],
            ['route' => 'roles.php', 'label' => 'Roles & access', 'icon' => 'fa-shield-halved'],
        ],
    ],
];
?>
<aside class="app-sidebar" data-app-sidebar aria-label="Primary navigation">
    <a class="app-brand" href="dashboard.php">
        <span class="app-brand-mark"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
        <span>
            <span class="app-brand-name">LAB AUTOMATION</span>
            <span class="app-brand-caption">Electrical testing workspace</span>
        </span>
    </a>

    <?php foreach ($navigationGroups as $group): ?>
        <?php $visibleItems = array_values(array_filter($group['items'], static fn (array $item): bool => app_can_access_route($item['route']))); ?>
        <?php if ($visibleItems !== []): ?>
            <nav class="app-nav-group" aria-label="<?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?>">
                <p class="app-nav-label"><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                <ul class="app-nav-list">
                    <?php foreach ($visibleItems as $item): ?>
                        <li>
                            <a class="app-nav-link<?php echo $activePage === $item['route'] ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['route'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activePage === $item['route'] ? ' aria-current="page"' : ''; ?>>
                                <i class="fa-solid <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="app-sidebar-bottom">
        <?php if (app_can_access_route('new-test.php')): ?>
            <a class="button button-primary w-full" href="new-test.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Record a test</a>
        <?php endif; ?>
        <div class="app-user-card">
            <span class="app-avatar"><?php echo htmlspecialchars($avatarInitials, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="app-user-copy">
                <strong><?php echo htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
        </div>
    </div>
</aside>
