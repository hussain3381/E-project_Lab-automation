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
    : ($isTester ? ' WHERE 1 = 0' : '');

$countSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN LOWER(t.status) IN ('pending', 'in progress', 'testing in progress', 'ready for retest') THEN 1 ELSE 0 END) AS open_count,
    SUM(CASE WHEN UPPER(t.result) = 'PASS' THEN 1 ELSE 0 END) AS pass_count,
    SUM(CASE WHEN UPPER(t.result) = 'FAIL' THEN 1 ELSE 0 END) AS fail_count,
    SUM(CASE WHEN UPPER(t.result) IN ('PENDING', '') THEN 1 ELSE 0 END) AS review_count
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
if (!$isTester) {
    $productStats = $conn->query("SELECT COUNT(*) AS total,
        SUM(status = 'Failed - Re-manufacturing') AS rework_count,
        SUM(cpri_status = 'Ready') AS cpri_count FROM products")->fetch_assoc() ?: [];
    $totalProducts = (int) ($productStats['total'] ?? 0);
    $productsInRework = (int) ($productStats['rework_count'] ?? 0);
    $cpriReady = (int) ($productStats['cpri_count'] ?? 0);
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
    'Administrator' => ['eyebrow' => 'SYSTEM CONTROL', 'title' => 'Your lab, at a glance', 'description' => 'Manage accounts and configuration while keeping the full laboratory workflow in view.'],
    'Lab Manager' => ['eyebrow' => 'OPERATIONS', 'title' => 'Keep the lab moving', 'description' => 'Track product intake, test progress, staff setup and controlled quality workflow.'],
    'Tester' => ['eyebrow' => 'MY WORKSPACE', 'title' => 'Your assigned test work', 'description' => 'Review your assigned tests, record results and follow the history for your lab work.'],
    'Quality Control' => ['eyebrow' => 'QUALITY REVIEW', 'title' => 'Review the quality queue', 'description' => 'Focus on pending outcomes, failures and products eligible for the next controlled workflow step.'],
];
$copy = $roleCopy[$role] ?? $roleCopy['Tester'];
$pageTitle = $copy['title'];
$pageEyebrow = $copy['eyebrow'];
$pageDescription = $copy['description'];
$pageActionHtml = app_can_access_route('new-test.php')
    ? '<a class="button button-primary" href="new-test.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Record a test</a>'
    : '<a class="button button-secondary" href="testing.php"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Open review queue</a>';
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
        ['label' => 'Failed', 'value' => $failedTests, 'note' => 'Needs manager review', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
} elseif ($role === 'Quality Control') {
    $metrics = [
        ['label' => 'Review queue', 'value' => $reviewTests, 'note' => 'Pending result decisions', 'icon' => 'fa-clipboard-list', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Failed tests', 'value' => $failedTests, 'note' => 'Check re-manufacture routing', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
        ['label' => 'CPRI ready', 'value' => $cpriReady, 'note' => 'Manual handoff eligible', 'icon' => 'fa-arrow-up-right-from-square', 'tone' => 'var(--theme-info)'],
        ['label' => 'Products in rework', 'value' => $productsInRework, 'note' => 'Re-manufacture workflow', 'icon' => 'fa-arrows-rotate', 'tone' => 'var(--theme-warning)'],
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

<?php if ($isTester && $testerProfile === null): ?>
    <div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Your login is not linked to a tester profile yet. Ask a Lab Manager to link your account before recording assigned work.</div>
<?php endif; ?>

<?php if ($role === 'Administrator'): ?>
    <section class="feature-card mb-4 flex flex-wrap items-center justify-between gap-4" data-reveal>
        <div><p class="eyebrow">ADMINISTRATION</p><h2 class="m-0 text-xl font-bold text-lab-text">System access and setup</h2><p class="mt-2 text-sm text-lab-muted">Create staff logins, review roles and maintain the catalogue.</p></div>
        <div class="action-row">
            <a class="button button-secondary" href="users.php"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> Manage accounts</a>
            <a class="button button-quiet" href="roles.php"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Roles</a>
            <a class="button button-quiet" href="product-catalog.php"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> Catalog</a>
        </div>
    </section>
<?php elseif ($role === 'Lab Manager'): ?>
    <section class="feature-card mb-4 flex flex-wrap items-center justify-between gap-4" data-reveal>
        <div><p class="eyebrow">DAILY OPERATIONS</p><h2 class="m-0 text-xl font-bold text-lab-text">Operations shortcuts</h2><p class="mt-2 text-sm text-lab-muted">Only the tools enabled for the Lab Manager role are shown.</p></div>
        <div class="action-row">
            <a class="button button-secondary" href="add-product.php"><i class="fa-solid fa-cube" aria-hidden="true"></i> Add product</a>
            <a class="button button-quiet" href="testers.php"><i class="fa-solid fa-user-group" aria-hidden="true"></i> Staff</a>
            <a class="button button-quiet" href="product-workflow.php"><i class="fa-solid fa-arrows-spin" aria-hidden="true"></i> Workflow</a>
        </div>
    </section>
<?php elseif ($role === 'Quality Control'): ?>
    <section class="feature-card mb-4 flex flex-wrap items-center justify-between gap-4" data-reveal>
        <div><p class="eyebrow">QUALITY CONTROL</p><h2 class="m-0 text-xl font-bold text-lab-text">Review before release</h2><p class="mt-2 text-sm text-lab-muted">Failed outcomes remain in the re-manufacture workflow; CPRI handoff is always manual.</p></div>
        <div class="action-row"><a class="button button-secondary" href="product-workflow.php"><i class="fa-solid fa-arrows-spin" aria-hidden="true"></i> Open quality workflow</a><a class="button button-quiet" href="reports.php"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Review reports</a></div>
    </section>
<?php else: ?>
    <section class="feature-card mb-4" data-reveal>
        <p class="eyebrow">TESTER WORKFLOW</p><h2 class="m-0 text-xl font-bold text-lab-text">Capture results with context</h2>
        <p class="mt-2 text-sm leading-7 text-lab-muted">Record criteria, expected and actual output, outcome, date and remarks. A failure remains visible for manager review and re-manufacture routing.</p>
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

    <aside class="panel" data-reveal>
        <header class="panel-header"><div><h2>Your next steps</h2><p>Shortcuts are based on your role.</p></div></header>
        <div class="panel-body grid gap-3">
            <?php if (app_can_access_route('new-test.php')): ?><a class="app-nav-link" href="new-test.php"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Record a test result</span></a><?php endif; ?>
            <a class="app-nav-link" href="products.php"><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i><span>Browse products</span></a>
            <a class="app-nav-link" href="search.php"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Find a Product ID / Test ID</span></a>
            <?php if (app_can_access_route('reports.php')): ?><a class="app-nav-link" href="reports.php"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Review reports</span></a><?php endif; ?>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
