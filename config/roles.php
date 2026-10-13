<?php
// Human-readable access summary for the built-in roles; route gates remain authoritative.

declare(strict_types=1);

return [
    'Administrator' => [
        'summary' => 'Full access to system setup, users, role registry, catalogues, test plans, and every workflow action.',
        'access' => ['Dashboard and reports', 'Products and testing', 'Product catalogues and family test plans', 'Departments and full product workflow', 'Users and role registry', 'Settings'],
    ],
    'Lab Manager' => [
        'summary' => 'Registers products, assigns the next pending test, edits test details, and monitors laboratory progress.',
        'access' => ['Dashboard and reports', 'Register and edit products', 'Assign pending tests to testers', 'Edit test details with an audit trail', 'Departments, test types, and tester profiles', 'My profile and dashboard preferences'],
    ],
    'Tester' => [
        'summary' => 'Completes only pending tests assigned by an Administrator, Lab Manager, or Quality Control and reviews their own assigned work.',
        'access' => ['Dashboard and assigned test history', 'Complete assigned pending tests', 'Search assigned products and tests'],
    ],
    'Quality Control' => [
        'summary' => 'Reviews quality outcomes, confirms rework, assigns retests when needed, records CPRI results, and releases approved products.',
        'access' => ['Dashboard and test history', 'Review quality results', 'Assign a pending test to an available tester', 'Record re-manufacture completion and CPRI handoff/result', 'Record market release after CPRI approval', 'My profile and dashboard preferences'],
    ],
];
