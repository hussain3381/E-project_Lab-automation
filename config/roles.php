<?php
// Human-readable access summary for the built-in roles; route gates remain authoritative.

declare(strict_types=1);

return [
    'Administrator' => [
        'summary' => 'Full access to system setup, users, role registry, catalogues, test plans, and workflow controls.',
        'access' => ['Dashboard and reports', 'Products and testing', 'Product catalogues and family test plans', 'Departments and workflow', 'Users and role registry', 'Settings'],
    ],
    'Lab Manager' => [
        'summary' => 'Runs laboratory operations and supervises product/test workflow.',
        'access' => ['Dashboard and reports', 'Products and testing', 'Departments, test types, and testers', 'Re-manufacture and CPRI workflow', 'Settings'],
    ],
    'Tester' => [
        'summary' => 'Enters and reviews laboratory test results for assigned products.',
        'access' => ['Dashboard and test history', 'Record assigned test results', 'Search products and tests'],
    ],
    'Quality Control' => [
        'summary' => 'Reviews quality outcomes and supports controlled release/handoff actions.',
        'access' => ['Dashboard and test history', 'Review quality results', 'Re-manufacture and CPRI workflow'],
    ],
];
