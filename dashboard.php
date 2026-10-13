<?php
// Role-specific workspace landing. The same permission matrix controls route access and navigation.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/Tester.php';

$role = (string) ($_SESSION['role'] ?? 'Tester');
$userId = (int) ($_SESSION['user_id'] ?? 0);
$isTester = $role === 'Tester';
$testerProfile = $isTester ? Tester::findByUserId($conn, $userId) : null;
$testerId = $testerProfile !== null ? (int) $testerProfile['id'] : 0;
$testerScope = $isTester && $testerId > 0
    ? ' WHERE (t.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS p WHERE p.test_record_id = t.id AND p.tester_id = ?))'
        . ' AND EXISTS (SELECT 1 FROM products AS active_product WHERE active_product.product_id = t.product_id AND active_product.is_archived = 0)'
    : ($isTester ? ' WHERE 1 = 0' : '');

$currentAssignmentExists = "EXISTS (SELECT 1 FROM products AS current_product WHERE current_product.product_id = t.product_id "
    . "AND current_product.is_archived = 0 AND t.cycle_number = current_product.rework_cycle + 1 "
    . "AND current_product.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest'))";
$countSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN LOWER(t.status) IN ('pending', 'in progress', 'testing in progress', 'ready for retest') AND {$currentAssignmentExists} THEN 1 ELSE 0 END) AS open_count,
    SUM(CASE WHEN UPPER(t.result) = 'PASS' THEN 1 ELSE 0 END) AS pass_count,
    SUM(CASE WHEN UPPER(t.result) = 'FAIL' THEN 1 ELSE 0 END) AS fail_count,
    SUM(CASE WHEN UPPER(t.result) IN ('PENDING', '') AND {$currentAssignmentExists} THEN 1 ELSE 0 END) AS review_count
    FROM tests AS t{$testerScope}";
$countStatement = $conn->prepare($countSql);
if ($isTester && $testerId > 0) {
    $countStatement->bind_param('ii', $testerId, $testerId);
}
$countStatement->execute();
$counts = $countStatement->get_result()->fetch_assoc() ?: [];
$countStatement->close();

$totalProducts = 0;
$productsInRework = 0;
$cpriReady = 0;
$cpriWaitingResult = 0;
$cpriApprovedForRelease = 0;
$activeAccounts = 0;
$roleCounts = [];
$recentAuditRows = [];
if (!$isTester) {
    $productStats = $conn->query("SELECT COUNT(*) AS total,
        SUM(status = 'Failed - Re-manufacturing') AS rework_count,
        SUM(cpri_status = 'Ready') AS cpri_count,
        SUM(cpri_status = 'Handed Off') AS cpri_waiting_result,
        SUM(cpri_status = 'Approved') AS cpri_approved_count
        FROM products WHERE is_archived = 0")->fetch_assoc() ?: [];
    $totalProducts = (int) ($productStats['total'] ?? 0);
    $productsInRework = (int) ($productStats['rework_count'] ?? 0);
    $cpriReady = (int) ($productStats['cpri_count'] ?? 0);
    $cpriWaitingResult = (int) ($productStats['cpri_waiting_result'] ?? 0);
    $cpriApprovedForRelease = (int) ($productStats['cpri_approved_count'] ?? 0);
}
if ($role === 'Administrator') {
    $accountStats = $conn->query(
        'SELECT COUNT(*) AS total, SUM(is_active = 1) AS active FROM users'
    )->fetch_assoc() ?: [];
    $activeAccounts = (int) ($accountStats['active'] ?? 0);
    $roleCounts = $conn->query(
        'SELECT COALESCE(r.role_name, u.role) AS role, COUNT(*) AS total, SUM(u.is_active = 1) AS active FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id GROUP BY r.id, r.role_name, u.role ORDER BY role'
    )->fetch_all(MYSQLI_ASSOC);
    $recentAuditRows = $conn->query(
        'SELECT actor_name, actor_role, action, entity, entity_id, summary, created_at FROM audit_logs ORDER BY id DESC LIMIT 8'
    )->fetch_all(MYSQLI_ASSOC);
}

$recentSql = "SELECT t.id, t.test_id, t.product_id, t.result, t.status, t.testing_date,
        p.product_name, tt.test_name
    FROM tests AS t
    LEFT JOIN products AS p ON p.product_id = t.product_id
    LEFT JOIN test_types AS tt ON tt.id = t.test_type_id
    {$testerScope}
    ORDER BY t.testing_date DESC, t.id DESC
    LIMIT 6";
$recentStatement = $conn->prepare($recentSql);
if ($isTester && $testerId > 0) {
    $recentStatement->bind_param('ii', $testerId, $testerId);
}
$recentStatement->execute();
$recentRows = $recentStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStatement->close();

$roleCopy = [
    'Administrator' => ['eyebrow' => 'SYSTEM CONTROL', 'title' => 'Laboratory command center', 'description' => 'A clear snapshot of products, current-cycle work, quality decisions and system activity.'],
    'Lab Manager' => ['eyebrow' => 'OPERATIONS', 'title' => 'Keep the lab moving', 'description' => 'Register products, assign the next test to a tester, and monitor the current cycle.'],
    'Tester' => ['eyebrow' => 'MY WORKSPACE', 'title' => 'Your assigned test work', 'description' => 'Review your assigned tests, record results and follow the history for your lab work.'],
    'Quality Control' => ['eyebrow' => 'QUALITY REVIEW', 'title' => 'Review the quality queue', 'description' => 'Confirm rework, assign retests when needed, record manual CPRI outcomes, and release approved products.'],
];
$copy = $roleCopy[$role] ?? $roleCopy['Tester'];
$pageTitle = $copy['title'];
$pageEyebrow = $copy['eyebrow'];
$pageDescription = $copy['description'];
$pageStylesheet = 'assets/css/pages/dashboard-overview.css';

// Reuse the same role-specific action as the sidebar so the main call-to-action is predictable.
$dashboardNavigation = require __DIR__ . '/config/navigation.php';
$dashboardRoleNavigation = $dashboardNavigation[$role] ?? $dashboardNavigation['Lab Manager'];
$dashboardPrimaryAction = $dashboardRoleNavigation['primary'] ?? null;
$dashboardSecondaryAction = $dashboardRoleNavigation['secondary'] ?? null;
$pageActionHtml = '';
if (is_array($dashboardPrimaryAction) && app_can_access_route($dashboardPrimaryAction['route'])) {
    $pageActionHtml = '<a class="button button-primary" href="' . htmlspecialchars($dashboardPrimaryAction['route'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-solid ' . htmlspecialchars($dashboardPrimaryAction['icon'], ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i> ' . htmlspecialchars($dashboardPrimaryAction['label'], ENT_QUOTES, 'UTF-8') . '</a>';
}
if (is_array($dashboardSecondaryAction) && app_can_access_route($dashboardSecondaryAction['route'])) {
    $pageActionHtml .= '<a class="button button-secondary" href="' . htmlspecialchars($dashboardSecondaryAction['route'], ENT_QUOTES, 'UTF-8') . '"><i class="fa-solid ' . htmlspecialchars($dashboardSecondaryAction['icon'], ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i> ' . htmlspecialchars($dashboardSecondaryAction['label'], ENT_QUOTES, 'UTF-8') . '</a>';
}

$roleGuides = [
    'Administrator' => [
        'title' => 'Start here: set up the lab',
        'description' => 'Setup is mostly a one-time task. Once it is ready, the Manager can run daily testing.',
        'steps' => [
            ['title' => 'Create product families and model codes', 'text' => 'Add the product families and the exact model codes used when registering each unit.', 'route' => 'product-catalog.php', 'link' => 'Open product catalogue'],
            ['title' => 'Set departments, test types and family plans', 'text' => 'Define where each test happens, then mark which tests are required for CPRI readiness.', 'route' => 'product-test-plan.php', 'link' => 'Set family test plans'],
            ['title' => 'Add the staff accounts', 'text' => 'Create the Manager, Tester and Quality Control accounts with the right role.', 'route' => 'users.php', 'link' => 'Manage user accounts'],
        ],
    ],
    'Lab Manager' => [
        'title' => 'Start here: move a product through the lab',
        'description' => 'Register the unit, assign its tests, then follow the result and the next required action.',
        'steps' => [
            ['title' => 'Register the manufactured unit', 'text' => 'Add one product record with its model, revision and manufacturing number.', 'route' => 'add-product.php', 'link' => 'Add a product'],
            ['title' => 'Assign the test and tester', 'text' => 'Choose a test type from the family plan, confirm its department and assign the tester.', 'route' => 'new-test.php', 'link' => 'Assign a test'],
            ['title' => 'Monitor progress and route quality issues', 'text' => 'Check the current test cycle and send failures to Quality Control for rework and CPRI decisions.', 'route' => 'testing-status.php', 'link' => 'Review test status'],
        ],
    ],
    'Tester' => [
        'title' => 'Start here: complete your assigned test',
        'description' => 'You only need to work on tests assigned to your linked tester profile.',
        'steps' => [
            ['title' => 'Open My assigned tests', 'text' => 'Choose an open assignment for the product and test type you are working on.', 'route' => 'testing.php', 'link' => 'Open my tests'],
            ['title' => 'Check the criteria and expected output', 'text' => 'Read what the test checks and what result is expected before recording measurements.'],
            ['title' => 'Enter the actual result', 'text' => 'Save the actual output, choose PASS or FAIL, add remarks, and submit the assigned test.'],
        ],
    ],
    'Quality Control' => [
        'title' => 'Start here: review quality and next actions',
        'description' => 'Confirm physical rework, assign retests when needed, record CPRI results manually, and release only after CPRI approval.',
        'steps' => [
            ['title' => 'Review pending and failed results', 'text' => 'Check the test status and open the linked product/test history before taking action.', 'route' => 'testing-status.php', 'link' => 'Review test status'],
            ['title' => 'Confirm rework, then assign retests', 'text' => 'After physical re-manufacturing is confirmed, the Manager or QC can assign required tests in the new cycle.', 'route' => 'product-workflow.php', 'link' => 'Confirm rework'],
            ['title' => 'Assign a pending retest when needed', 'text' => 'Choose the next unassigned family-plan test and one or more available testers. Only one open test can run per product at a time.', 'route' => 'new-test.php', 'link' => 'Assign a retest'],
            ['title' => 'Record CPRI result and market release', 'text' => 'Enter the external CPRI PASS/FAIL manually. A PASS enables the final market-release action; a rejection routes to re-manufacture.', 'route' => 'product-workflow.php', 'link' => 'Review CPRI and release actions'],
        ],
    ],
];
$roleGuide = $roleGuides[$role] ?? $roleGuides['Tester'];
require __DIR__ . '/views/layouts/app_start.php';

$totalTests = (int) ($counts['total'] ?? 0);
$openTests = (int) ($counts['open_count'] ?? 0);
$passedTests = (int) ($counts['pass_count'] ?? 0);
$failedTests = (int) ($counts['fail_count'] ?? 0);
$reviewTests = (int) ($counts['review_count'] ?? 0);

if ($role === 'Tester') {
    $metrics = [
        ['label' => 'Assigned tests', 'value' => $totalTests, 'note' => 'Tests linked to your profile', 'icon' => 'fa-flask-vial', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Open work', 'value' => $openTests, 'note' => 'Pending or in progress', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Passed', 'value' => $passedTests, 'note' => 'Completed successfully', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'Failed', 'value' => $failedTests, 'note' => 'Needs Quality Control review', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
} elseif ($role === 'Quality Control') {
    $metrics = [
        ['label' => 'Review queue', 'value' => $reviewTests, 'note' => 'Pending result decisions', 'icon' => 'fa-clipboard-list', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Products in rework', 'value' => $productsInRework, 'note' => 'QC confirms physical rework', 'icon' => 'fa-arrows-rotate', 'tone' => 'var(--theme-danger)'],
        ['label' => 'CPRI ready', 'value' => $cpriReady, 'note' => 'Ready for manual handoff', 'icon' => 'fa-arrow-up-right-from-square', 'tone' => 'var(--theme-info)'],
        ['label' => 'CPRI follow-up', 'value' => $cpriWaitingResult + $cpriApprovedForRelease, 'note' => 'Result pending or release due', 'icon' => 'fa-clipboard-check', 'tone' => 'var(--theme-warning)'],
    ];
} elseif ($role === 'Administrator') {
    $metrics = [
        ['label' => 'Products', 'value' => $totalProducts, 'note' => 'Active product records', 'icon' => 'fa-cubes-stacked', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Active accounts', 'value' => $activeAccounts, 'note' => 'Across all four roles', 'icon' => 'fa-users', 'tone' => 'var(--theme-info)'],
        ['label' => 'Tests in progress', 'value' => $openTests, 'note' => 'Pending or in progress', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Failed tests', 'value' => $failedTests, 'note' => 'Check re-manufacture routing', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
} else {
    $metrics = [
        ['label' => 'Products', 'value' => $totalProducts, 'note' => 'Registered product records', 'icon' => 'fa-cubes-stacked', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Tests in progress', 'value' => $openTests, 'note' => 'Pending or in progress', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Tests passed', 'value' => $passedTests, 'note' => 'Current recorded outcomes', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'Failed tests', 'value' => $failedTests, 'note' => 'Review rework routing', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
}
?>
<section class="stats-grid">
    <?php foreach ($metrics as $metric): ?>
        <?php
        $statLabel = $metric['label'];
        $statValue = number_format((int) $metric['value']);
        $statNote = $metric['note'];
        $statIcon = $metric['icon'];
        $statTone = $metric['tone'];
        require __DIR__ . '/views/components/stat-card.php';
        ?>
    <?php endforeach; ?>
</section>

<?php if ($role === 'Administrator'): ?>
    <?php
        $snapshotLoadedAt = new DateTimeImmutable('now');
        $adminPulseItems = [
            ['icon' => 'fa-boxes-stacked', 'label' => 'Active products', 'value' => $totalProducts],
            ['icon' => 'fa-users', 'label' => 'Active accounts', 'value' => $activeAccounts],
            ['icon' => 'fa-hourglass-half', 'label' => 'Open tests', 'value' => $openTests],
            ['icon' => 'fa-triangle-exclamation', 'label' => 'FAIL results', 'value' => $failedTests],
            ['icon' => 'fa-arrow-up-right-from-square', 'label' => 'CPRI ready', 'value' => $cpriReady],
            ['icon' => 'fa-clipboard-check', 'label' => 'CPRI follow-up', 'value' => $cpriWaitingResult + $cpriApprovedForRelease],
        ];
    ?>
    <section class="admin-overview-spotlight" data-reveal aria-label="Administrator system overview">
        <div class="admin-overview-copy">
            <span class="admin-overview-kicker"><span class="admin-live-dot" aria-hidden="true"></span> ADMIN OVERVIEW</span>
            <h2>Lab control, at a glance.</h2>
            <p>A focused snapshot of product flow, team access, current test work and quality decisions—together in one workspace.</p>
        </div>
        <div class="admin-clock-card">
            <span class="admin-clock-label"><i class="fa-regular fa-clock" aria-hidden="true"></i> Workspace clock</span>
            <time datetime="<?php echo htmlspecialchars($snapshotLoadedAt->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8'); ?>" data-live-clock><?php echo htmlspecialchars($snapshotLoadedAt->format('D, d M Y · H:i:s'), ENT_QUOTES, 'UTF-8'); ?></time>
            <small>Records reflect this page load · refresh to sync changes</small>
        </div>
    </section>
    <section class="admin-pulse" aria-label="Administrator dashboard snapshot">
        <div class="admin-pulse-meta">
            <span><span class="admin-pulse-indicator" aria-hidden="true"></span> LAB PULSE</span>
            <small>Snapshot · <?php echo htmlspecialchars($snapshotLoadedAt->format('d M Y · H:i'), ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
        <div class="admin-pulse-window">
            <div class="admin-pulse-track" aria-live="off">
                <?php for ($pulseCopy = 0; $pulseCopy < 2; $pulseCopy++): ?>
                    <div class="admin-pulse-group"<?php echo $pulseCopy === 1 ? ' aria-hidden="true"' : ''; ?>>
                        <?php foreach ($adminPulseItems as $pulseItem): ?>
                            <span class="admin-pulse-item">
                                <i class="fa-solid <?php echo htmlspecialchars((string) $pulseItem['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars((string) $pulseItem['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo number_format((int) $pulseItem['value']); ?></strong>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($isTester && $testerProfile === null): ?>
    <div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Your login is not linked to a tester profile yet. Ask a Lab Manager to link your account before recording assigned work.</div>
<?php endif; ?>

<section class="role-guide panel mb-4" data-reveal aria-labelledby="role-guide-title">
    <header class="panel-header">
        <div>
            <p class="eyebrow">START HERE</p>
            <h2 id="role-guide-title"><?php echo htmlspecialchars($roleGuide['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars($roleGuide['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </header>
    <ol class="role-guide-steps">
        <?php foreach ($roleGuide['steps'] as $stepIndex => $guideStep): ?>
            <li class="role-guide-step">
                <span class="role-guide-number" aria-hidden="true"><?php echo sprintf('%02d', $stepIndex + 1); ?></span>
                <div class="role-guide-copy">
                    <strong><?php echo htmlspecialchars($guideStep['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?php echo htmlspecialchars($guideStep['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (isset($guideStep['route'], $guideStep['link']) && app_can_access_route($guideStep['route'])): ?>
                        <a class="role-guide-link" href="<?php echo htmlspecialchars($guideStep['route'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($guideStep['link'], ENT_QUOTES, 'UTF-8'); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<?php if ($role === 'Administrator'): ?>
    <section class="panel mb-4" data-reveal>
        <header class="panel-header"><div><h2>Accounts by role</h2><p>Current access assignments and active seats.</p></div><a class="button button-quiet" href="users.php">Manage users <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></header>
        <div class="role-count-grid">
            <?php foreach ($roleCounts as $roleCount): ?>
                <div class="role-count-card"><span><?php echo htmlspecialchars((string) $roleCount['role'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $roleCount['active']; ?></strong><small>active / <?php echo (int) $roleCount['total']; ?> total</small></div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel mb-4" data-reveal>
        <header class="panel-header"><div><h2>Recent system activity</h2><p>Recent changes made by staff accounts.</p></div><a class="button button-quiet" href="admin-activity.php">Open full log <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></header>
        <?php if ($recentAuditRows === []): ?>
            <?php $emptyTitle = 'No system activity logged yet'; $emptyText = 'Create or update a user, product or test record to start the audit trail.'; $emptyIcon = 'fa-clock-rotate-left'; require __DIR__ . '/views/components/empty-state.php'; ?>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
                <tbody><?php foreach ($recentAuditRows as $auditRow): ?><tr>
                    <td><?php echo htmlspecialchars(date('d M H:i', strtotime((string) $auditRow['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $auditRow['actor_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) $auditRow['actor_role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars(str_replace('_', ' ', (string) $auditRow['action']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $auditRow['entity'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) ($auditRow['entity_id'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars((string) $auditRow['summary'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr><?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="dashboard-grid">
    <section class="panel" data-reveal>
        <header class="panel-header"><div><h2>Recent test activity</h2><p><?php echo $role === 'Tester' ? 'Only records assigned to your linked tester profile.' : 'Latest laboratory records across the current test cycle.'; ?></p></div><a class="button button-quiet" href="testing.php">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></header>
        <?php if ($recentRows === []): ?>
            <?php $emptyTitle = 'No test activity yet'; $emptyText = $role === 'Tester' ? 'When tests are assigned to your profile, they will appear here.' : 'New test records will appear here after the first submission.'; $emptyIcon = 'fa-flask'; require __DIR__ . '/views/components/empty-state.php'; ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Test / product</th><th>Test type</th><th>Date</th><th>Outcome</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentRows as $row): ?>
                        <tr>
                            <td><span class="table-primary"><?php echo htmlspecialchars((string) $row['test_id'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) $row['product_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string) ($row['test_name'] ?? 'Test type'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['testing_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php $badgeLabel = (string) $row['result']; $badgeTone = (string) $row['result']; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                            <td><a class="button button-quiet" href="test-details.php?id=<?php echo (int) $row['id']; ?>" aria-label="View test details"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <aside class="status-reference panel" data-reveal aria-labelledby="status-reference-title">
        <header class="panel-header"><div><h2 id="status-reference-title">Quick status guide</h2><p>What each result means.</p></div></header>
        <div class="panel-body status-reference-list">
            <p><span class="status-reference-dot is-pass"></span><strong>PASS</strong><span>This test passed.</span></p>
            <p><span class="status-reference-dot is-fail"></span><strong>FAIL</strong><span>Re-manufacturing is needed.</span></p>
            <p><span class="status-reference-dot is-pending"></span><strong>Pending</strong><span>A test result is still waiting.</span></p>
            <p><span class="status-reference-dot is-cpri"></span><strong>CPRI Ready</strong><span>All required internal tests passed; QC still needs to record the manual handoff.</span></p>
            <p><span class="status-reference-dot is-cpri"></span><strong>Handed to CPRI</strong><span>Handoff is logged; QC must manually enter the returned CPRI result.</span></p>
            <p><span class="status-reference-dot is-cpri"></span><strong>CPRI Approved</strong><span>CPRI PASS is recorded; market release still needs a separate QC action.</span></p>
            <p><span class="status-reference-dot is-cpri"></span><strong>Released to Market</strong><span>Final local market-release action is recorded.</span></p>
            <p><span class="status-reference-dot is-fail"></span><strong>CPRI rejected</strong><span>Routes to re-manufacture; after rework, repeat all required tests in a new cycle.</span></p>
            <a class="button button-secondary w-full" href="search.php"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Find a Product ID or Test ID</a>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
