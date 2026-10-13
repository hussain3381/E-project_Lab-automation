<?php
// One authoritative route matrix drives both page guards and visible navigation.
declare(strict_types=1);

$allStaff = ['Administrator', 'Lab Manager', 'Tester', 'Quality Control'];
$reportRoles = ['Administrator', 'Lab Manager', 'Quality Control'];
$labLeads = ['Administrator', 'Lab Manager'];
$workflowRoles = ['Administrator', 'Quality Control'];
$testAuthors = ['Administrator', 'Lab Manager', 'Quality Control'];

return [
    // Shared operational workspace.
    'dashboard.php' => $allStaff,
    'products.php' => $allStaff,
    'product-details.php' => $allStaff,
    'testing.php' => $allStaff,
    'test-details.php' => $allStaff,
    'edit-test.php' => ['Administrator', 'Lab Manager'],
    'profile.php' => $allStaff,
    'save-preferences.php' => $allStaff,
    'testing-status.php' => $reportRoles,
    'search.php' => $allStaff,
    'reports.php' => $reportRoles,

    // Managers and QC create pending assignments; Administrators also retain direct result-entry access.
    'new-test.php' => $testAuthors,
    'complete-test.php' => ['Tester'],

    // Laboratory operations, setup, and workflow.
    'add-product.php' => $labLeads,
    'edit-product.php' => $labLeads,
    'departments.php' => $labLeads,
    'test-types.php' => $labLeads,
    'testers.php' => $labLeads,
    'settings.php' => ['Administrator'], // Lab-wide settings are separate from each user's profile.
    'product-workflow.php' => $workflowRoles,

    // Administrator-only configuration and account management.
    'product-catalog.php' => ['Administrator'],
    'product-test-plan.php' => ['Administrator'],
    'users.php' => ['Administrator'],
    'roles.php' => ['Administrator'],
    'admin-activity.php' => ['Administrator'],
];
