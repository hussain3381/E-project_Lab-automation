<?php
// Summary analytics for lab leads and Quality Control, using saved laboratory records only.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);

$headline = $conn->query(
    "SELECT "
    . "(SELECT COUNT(*) FROM products WHERE is_archived = 0) AS active_products, "
    . "(SELECT COUNT(*) FROM products WHERE is_archived = 1) AS archived_products, "
    . "(SELECT COUNT(*) FROM tests) AS total_tests, "
    . "(SELECT COUNT(*) FROM tests WHERE UPPER(COALESCE(result, '')) = 'PASS') AS passed_tests, "
    . "(SELECT COUNT(*) FROM tests WHERE UPPER(COALESCE(result, '')) = 'FAIL') AS failed_tests, "
    . "(SELECT COUNT(*) FROM tests WHERE UPPER(COALESCE(result, 'PENDING')) IN ('PENDING', '')) AS pending_tests, "
    . "(SELECT COUNT(*) FROM products WHERE is_archived = 0 AND cpri_status = 'Handed Off') AS cpri_follow_up"
)->fetch_assoc() ?: [];

$activeProducts = (int) ($headline['active_products'] ?? 0);
$archivedProducts = (int) ($headline['archived_products'] ?? 0);
$totalTests = (int) ($headline['total_tests'] ?? 0);
$passedTests = (int) ($headline['passed_tests'] ?? 0);
$failedTests = (int) ($headline['failed_tests'] ?? 0);
$pendingTests = (int) ($headline['pending_tests'] ?? 0);
$cpriFollowUp = (int) ($headline['cpri_follow_up'] ?? 0);
$completedOutcomes = $passedTests + $failedTests;
$passRate = $completedOutcomes > 0 ? round(($passedTests / $completedOutcomes) * 100, 1) : 0.0;

$departmentRows = $conn->query(
    "SELECT COALESCE(d.department_name, tt.department, 'Unassigned') AS department_name, "
    . "COUNT(t.id) AS total_tests, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'PASS' THEN 1 ELSE 0 END) AS passed, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'FAIL' THEN 1 ELSE 0 END) AS failed "
    . "FROM test_types AS tt "
    . "LEFT JOIN departments AS d ON d.id = tt.department_id "
    . "LEFT JOIN tests AS t ON t.test_type_id = tt.id "
    . "GROUP BY COALESCE(d.department_name, tt.department, 'Unassigned') "
    . "ORDER BY total_tests DESC, department_name ASC"
)->fetch_all(MYSQLI_ASSOC);

$testTypeRows = $conn->query(
    "SELECT tt.id, tt.test_name, tt.test_code, COUNT(t.id) AS total_tests, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'PASS' THEN 1 ELSE 0 END) AS passed, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'FAIL' THEN 1 ELSE 0 END) AS failed "
    . "FROM test_types AS tt LEFT JOIN tests AS t ON t.test_type_id = tt.id "
    . "GROUP BY tt.id, tt.test_name, tt.test_code "
    . "ORDER BY total_tests DESC, tt.test_name ASC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

$productStateRows = $conn->query(
    'SELECT status, COUNT(*) AS total FROM products WHERE is_archived = 0 GROUP BY status ORDER BY total DESC, status ASC'
)->fetch_all(MYSQLI_ASSOC);
$largestState = max(1, ...array_map(static fn (array $row): int => (int) $row['total'], $productStateRows));

$recentStatement = $conn->prepare(
    'SELECT t.id, t.test_id, t.product_id, t.testing_date, t.result, t.status, t.cycle_number, '
    . 'p.product_name, p.status AS product_status, tt.test_name, tester.name AS tester_name '
    . 'FROM tests AS t LEFT JOIN products AS p ON p.product_id = t.product_id '
    . 'LEFT JOIN test_types AS tt ON tt.id = t.test_type_id '
    . 'LEFT JOIN testers AS tester ON tester.id = t.tester_id '
    . 'ORDER BY t.id DESC LIMIT 10'
);
$recentStatement->execute();
$recentTests = $recentStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStatement->close();

$pageTitle = 'Laboratory reports';
$pageEyebrow = 'LAB ANALYTICS';
$pageDescription = 'Review recorded outcomes, department activity, product workflow states, and the latest test history.';
$pageStylesheet = 'assets/css/pages/status-overview.css';
$pageActionHtml = '<a class="button button-quiet" href="testing-status.php"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Open status overview</a>';
require __DIR__ . '/views/layouts/app_start.php';
?>

<section class="stats-grid report-metric-grid" aria-label="Laboratory totals">
    <?php
    $reportMetrics = [
        ['label' => 'Active products', 'value' => $activeProducts, 'note' => number_format($archivedProducts) . ' archived records excluded', 'icon' => 'fa-cubes-stacked', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Test records', 'value' => $totalTests, 'note' => 'Includes historical cycles', 'icon' => 'fa-flask-vial', 'tone' => 'var(--theme-info)'],
        ['label' => 'Pass rate', 'value' => number_format($passRate, 1) . '%', 'note' => number_format($completedOutcomes) . ' recorded PASS/FAIL outcomes', 'icon' => 'fa-chart-line', 'tone' => 'var(--theme-success)'],
        ['label' => 'CPRI follow-up', 'value' => $cpriFollowUp, 'note' => 'Handoff entered; external result pending', 'icon' => 'fa-clipboard-check', 'tone' => 'var(--theme-warning)'],
    ];
    foreach ($reportMetrics as $metric) {
        $statLabel = $metric['label'];
        $statValue = is_numeric($metric['value']) ? number_format((float) $metric['value']) : $metric['value'];
        $statNote = $metric['note'];
        $statIcon = $metric['icon'];
        $statTone = $metric['tone'];
        require __DIR__ . '/views/components/stat-card.php';
    }
    ?>
</section>

<section class="reports-top-grid">
    <article class="panel report-pass-panel" data-reveal>
        <header class="panel-header"><div><p class="eyebrow">RECORDED OUTCOMES</p><h2>Pass rate</h2><p>PASS divided by completed PASS/FAIL results; pending tests are not included.</p></div></header>
        <div class="panel-body report-pass-body">
            <div class="report-rate-ring" style="--pass-rate: <?php echo htmlspecialchars((string) $passRate, ENT_QUOTES, 'UTF-8'); ?>%">
                <div><strong><?php echo number_format($passRate, 1); ?>%</strong><span>PASS rate</span></div>
            </div>
            <div class="report-rate-legend">
                <div><span class="report-legend-dot is-pass"></span><span>Passed</span><strong><?php echo number_format($passedTests); ?></strong></div>
                <div><span class="report-legend-dot is-fail"></span><span>Failed</span><strong><?php echo number_format($failedTests); ?></strong></div>
                <div><span class="report-legend-dot is-pending"></span><span>Pending</span><strong><?php echo number_format($pendingTests); ?></strong></div>
            </div>
        </div>
    </article>

    <article class="panel report-workflow-panel" data-reveal>
        <header class="panel-header"><div><p class="eyebrow">PRODUCT FLOW</p><h2>Active product states</h2><p>Current workflow status for non-archived products.</p></div></header>
        <div class="panel-body report-workflow-list">
            <?php if ($productStateRows === []): ?>
                <div class="empty-state"><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i><p>No active product records yet.</p></div>
            <?php else: ?>
                <?php foreach ($productStateRows as $stateRow): ?>
                    <?php $statePercent = min(100, round(((int) $stateRow['total'] / $largestState) * 100, 1)); ?>
                    <div class="report-state-row">
                        <div class="report-state-meta"><span><?php echo htmlspecialchars((string) $stateRow['status'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo number_format((int) $stateRow['total']); ?></strong></div>
                        <div class="report-state-track"><span style="width: <?php echo $statePercent; ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="panel report-breakdown-panel" data-reveal>
    <header class="panel-header"><div><p class="eyebrow">TEST DISTRIBUTION</p><h2>Activity by department</h2><p>Recorded tests are grouped using the department attached to each active test type.</p></div></header>
    <?php if ($departmentRows === []): ?>
        <?php $emptyTitle = 'No department data'; $emptyText = 'Add test types and connect them to departments to see activity here.'; $emptyIcon = 'fa-building'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Department</th><th>Test records</th><th>PASS</th><th>FAIL</th><th>Pass rate</th></tr></thead>
            <tbody>
            <?php foreach ($departmentRows as $department): ?>
                <?php $departmentOutcomes = (int) $department['passed'] + (int) $department['failed']; $departmentRate = $departmentOutcomes > 0 ? round(((int) $department['passed'] / $departmentOutcomes) * 100, 1) : 0.0; ?>
                <tr>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $department['department_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo number_format((int) $department['total_tests']); ?></td>
                    <td><span class="status-pill is-success"><?php echo number_format((int) $department['passed']); ?></span></td>
                    <td><span class="status-pill is-danger"><?php echo number_format((int) $department['failed']); ?></span></td>
                    <td><div class="report-inline-rate"><span><?php echo number_format($departmentRate, 1); ?>%</span><div><i style="width: <?php echo $departmentRate; ?>%"></i></div></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>

<section class="panel report-breakdown-panel" data-reveal>
    <header class="panel-header"><div><p class="eyebrow">TEST TYPE</p><h2>Most active test types</h2><p>Top ten by recorded test volume; outcomes include historic cycles.</p></div></header>
    <?php if ($testTypeRows === []): ?>
        <?php $emptyTitle = 'No test types configured'; $emptyText = 'Test-type volume will appear after the lab catalogue is set up.'; $emptyIcon = 'fa-list-check'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Test type</th><th>Code</th><th>Records</th><th>PASS</th><th>FAIL</th></tr></thead>
            <tbody>
            <?php foreach ($testTypeRows as $testType): ?>
                <tr><td><span class="table-primary"><?php echo htmlspecialchars((string) $testType['test_name'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars((string) $testType['test_code'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo number_format((int) $testType['total_tests']); ?></td><td><span class="status-pill is-success"><?php echo number_format((int) $testType['passed']); ?></span></td><td><span class="status-pill is-danger"><?php echo number_format((int) $testType['failed']); ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>

<section class="panel report-recent-panel" data-reveal>
    <header class="panel-header"><div><p class="eyebrow">LATEST ACTIVITY</p><h2>Recent tests</h2><p>Latest ten records by creation order.</p></div><a class="button button-quiet" href="testing.php">All test records <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></header>
    <?php if ($recentTests === []): ?>
        <?php $emptyTitle = 'No tests recorded yet'; $emptyText = 'Test assignments and their outcomes will appear here.'; $emptyIcon = 'fa-flask-vial'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Test ID</th><th>Product</th><th>Test type</th><th>Tester</th><th>Date</th><th>Cycle</th><th>Outcome</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentTests as $test): ?>
                <?php $resultLabel = strtoupper((string) ($test['result'] ?? 'PENDING')); if ($resultLabel === '') $resultLabel = 'PENDING'; ?>
                <tr>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $test['test_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $test['product_id'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) ($test['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars((string) ($test['test_name'] ?? 'Test type'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($test['tester_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($test['testing_date'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>Cycle <?php echo max(1, (int) ($test['cycle_number'] ?? 1)); ?></td>
                    <td><?php $badgeLabel = $resultLabel; $badgeTone = $resultLabel; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                    <td><a class="button button-quiet" href="test-details.php?id=<?php echo (int) $test['id']; ?>" aria-label="View test details"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>

<p class="reports-disclaimer"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Report values are summaries of database records. They do not verify measurements, certify a product, or replace the manual CPRI decision.</p>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
