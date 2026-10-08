<?php
// One authoritative route matrix drives both page guards and visible navigation.
declare(strict_types=1);

$allStaff = ['Administrator', 'Lab Manager', 'Tester', 'Quality Control'];
$reportRoles = ['Administrator', 'Lab Manager', 'Quality Control'];
$labLeads = ['Administrator', 'Lab Manager'];
$workflowRoles = ['Administrator', 'Lab Manager', 'Quality Control'];
$testAuthors = ['Administrator', 'Lab Manager', 'Tester'];

return [
    // Shared operational workspace.
    'dashboard.php' => $allStaff,
    'products.php' => $allStaff,
    'product-details.php' => $allStaff,
    'testing.php' => $allStaff,
    'test-details.php' => $allStaff,
    'testing-status.php' => $reportRoles,
    'search.php' => $allStaff,
    'reports.php' => $reportRoles,

    // Test data entry and assigned-record completion.
    'new-test.php' => $testAuthors,
    'complete-test.php' => ['Tester'],

    // Laboratory operations, setup, and workflow.
    'add-product.php' => $labLeads,
    'edit-product.php' => $labLeads,
    'departments.php' => $labLeads,
    'test-types.php' => $labLeads,
    'testers.php' => $labLeads,
    'settings.php' => $labLeads,
    'product-workflow.php' => $workflowRoles,

    // Administrator-only configuration and account management.
    'product-catalog.php' => ['Administrator'],
    'product-test-plan.php' => ['Administrator'],
    'users.php' => ['Administrator'],
    'roles.php' => ['Administrator'],
];
