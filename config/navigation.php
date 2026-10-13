<?php
// One role-aware navigation definition is shared by both workspace layouts.
declare(strict_types=1);

return [
    'Administrator' => [
        'groups' => [
            [
                'label' => 'Daily work',
                'items' => [
                    ['route' => 'dashboard.php', 'label' => 'Overview', 'icon' => 'fa-gauge-high'],
                    ['route' => 'products.php', 'label' => 'Products', 'icon' => 'fa-cubes-stacked'],
                    ['route' => 'testing.php', 'label' => 'Tests and results', 'icon' => 'fa-flask-vial'],
                    ['route' => 'testing-status.php', 'label' => 'Status overview', 'icon' => 'fa-chart-simple'],
                    ['route' => 'search.php', 'label' => 'Find a product or test', 'icon' => 'fa-magnifying-glass'],
                    ['route' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
                ],
            ],
            [
                'label' => 'Quality workflow',
                'items' => [
                    ['route' => 'product-workflow.php', 'label' => 'Rework, CPRI and release', 'icon' => 'fa-arrows-spin'],
                ],
            ],
            [
                'label' => 'Lab setup',
                'items' => [
                    ['route' => 'product-catalog.php', 'label' => 'Product catalogue', 'icon' => 'fa-boxes-stacked'],
                    ['route' => 'product-test-plan.php', 'label' => 'Tests by product family', 'icon' => 'fa-diagram-project'],
                    ['route' => 'departments.php', 'label' => 'Departments', 'icon' => 'fa-building'],
                    ['route' => 'test-types.php', 'label' => 'Test types', 'icon' => 'fa-list-check'],
                    ['route' => 'testers.php', 'label' => 'Tester profiles', 'icon' => 'fa-user-group'],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    ['route' => 'users.php', 'label' => 'User accounts', 'icon' => 'fa-users-gear'],
                    ['route' => 'roles.php', 'label' => 'Roles and access', 'icon' => 'fa-shield-halved'],
                    ['route' => 'settings.php', 'label' => 'System settings', 'icon' => 'fa-sliders'],
                    ['route' => 'admin-activity.php', 'label' => 'Activity log', 'icon' => 'fa-clock-rotate-left'],
                ],
            ],
            [
                'label' => 'My account',
                'items' => [
                    ['route' => 'profile.php', 'label' => 'My profile', 'icon' => 'fa-user-gear'],
                ],
            ],
        ],
        'primary' => ['route' => 'add-product.php', 'label' => 'Add a product', 'icon' => 'fa-plus'],
        'secondary' => ['route' => 'new-test.php', 'label' => 'Assign a test', 'icon' => 'fa-flask-vial'],
    ],
    'Lab Manager' => [
        'groups' => [
            [
                'label' => 'Daily work',
                'items' => [
                    ['route' => 'dashboard.php', 'label' => 'Overview', 'icon' => 'fa-gauge-high'],
                    ['route' => 'products.php', 'label' => 'Products', 'icon' => 'fa-cubes-stacked'],
                    ['route' => 'testing.php', 'label' => 'Tests and results', 'icon' => 'fa-flask-vial'],
                    ['route' => 'testing-status.php', 'label' => 'Status overview', 'icon' => 'fa-chart-simple'],
                    ['route' => 'search.php', 'label' => 'Find a product or test', 'icon' => 'fa-magnifying-glass'],
                    ['route' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
                ],
            ],
            [
                'label' => 'Team and lab setup',
                'items' => [
                    ['route' => 'departments.php', 'label' => 'Departments', 'icon' => 'fa-building'],
                    ['route' => 'test-types.php', 'label' => 'Test types', 'icon' => 'fa-list-check'],
                    ['route' => 'testers.php', 'label' => 'Tester profiles', 'icon' => 'fa-user-group'],
                ],
            ],
            [
                'label' => 'My account',
                'items' => [
                    ['route' => 'profile.php', 'label' => 'My profile', 'icon' => 'fa-user-gear'],
                ],
            ],
        ],
        'primary' => ['route' => 'add-product.php', 'label' => 'Add a product', 'icon' => 'fa-plus'],
        'secondary' => ['route' => 'new-test.php', 'label' => 'Assign a test', 'icon' => 'fa-flask-vial'],
    ],
    'Tester' => [
        'groups' => [
            [
                'label' => 'My work',
                'items' => [
                    ['route' => 'dashboard.php', 'label' => 'Overview', 'icon' => 'fa-gauge-high'],
                    ['route' => 'testing.php', 'label' => 'My assigned tests', 'icon' => 'fa-flask-vial'],
                    ['route' => 'products.php', 'label' => 'My assigned products', 'icon' => 'fa-cubes-stacked'],
                    ['route' => 'search.php', 'label' => 'Find an assigned record', 'icon' => 'fa-magnifying-glass'],
                ],
            ],
            [
                'label' => 'My account',
                'items' => [
                    ['route' => 'profile.php', 'label' => 'My profile', 'icon' => 'fa-user-gear'],
                ],
            ],
        ],
        // Testers start from their assigned queue; the server also denies test creation.
        'primary' => ['route' => 'testing.php', 'label' => 'Open my assigned tests', 'icon' => 'fa-arrow-right'],
        'secondary' => null,
    ],
    'Quality Control' => [
        'groups' => [
            [
                'label' => 'Quality review',
                'items' => [
                    ['route' => 'dashboard.php', 'label' => 'Overview', 'icon' => 'fa-gauge-high'],
                    ['route' => 'testing-status.php', 'label' => 'Review test status', 'icon' => 'fa-chart-simple'],
                    ['route' => 'testing.php', 'label' => 'Test records', 'icon' => 'fa-flask-vial'],
                    ['route' => 'products.php', 'label' => 'Products', 'icon' => 'fa-cubes-stacked'],
                    ['route' => 'search.php', 'label' => 'Find a product or test', 'icon' => 'fa-magnifying-glass'],
                    ['route' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
                ],
            ],
            [
                'label' => 'Next workflow action',
                'items' => [
                    ['route' => 'product-workflow.php', 'label' => 'Rework, CPRI and release', 'icon' => 'fa-arrows-spin'],
                    ['route' => 'new-test.php', 'label' => 'Assign a retest', 'icon' => 'fa-user-check'],
                ],
            ],
            [
                'label' => 'My account',
                'items' => [
                    ['route' => 'profile.php', 'label' => 'My profile', 'icon' => 'fa-user-gear'],
                ],
            ],
        ],
        'primary' => ['route' => 'product-workflow.php', 'label' => 'Review next steps', 'icon' => 'fa-arrow-right'],
        'secondary' => ['route' => 'new-test.php', 'label' => 'Assign a retest', 'icon' => 'fa-user-check'],
    ],
];
