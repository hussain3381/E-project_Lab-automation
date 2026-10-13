<?php
// Current-cycle status view shared by Administrators, Lab Managers, and Quality Control.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);

$currentCycle = '(p.is_archived = 0 AND t.cycle_number = p.rework_cycle + 1)';
$activeProduct = "(p.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest') "
    . "AND COALESCE(p.cpri_status, 'Not Ready') NOT IN ('Handed Off', 'Approved', 'Rejected', 'Released'))";
$openAssignment = "(UPPER(COALESCE(t.result, 'PENDING')) IN ('PENDING', '') "
    . "OR LOWER(COALESCE(t.status, '')) IN ('pending', 'in progress', 'testing in progress'))";

$summarySql = "SELECT COUNT(t.id) AS total_tests, "
    . "SUM(CASE WHEN {$currentCycle} AND {$activeProduct} AND {$openAssignment} THEN 1 ELSE 0 END) AS open_current, "
    . "SUM(CASE WHEN {$currentCycle} AND {$activeProduct} AND {$openAssignment} AND LOWER(COALESCE(t.status, '')) = 'pending' THEN 1 ELSE 0 END) AS pending_current, "
    . "SUM(CASE WHEN {$currentCycle} AND {$activeProduct} AND {$openAssignment} AND LOWER(COALESCE(t.status, '')) IN ('in progress', 'testing in progress') THEN 1 ELSE 0 END) AS in_progress_current, "
    . "SUM(CASE WHEN {$currentCycle} AND (LOWER(COALESCE(t.status, '')) = 'completed' OR UPPER(COALESCE(t.result, '')) IN ('PASS', 'FAIL')) THEN 1 ELSE 0 END) AS completed_current, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'PASS' THEN 1 ELSE 0 END) AS passed_total, "
    . "SUM(CASE WHEN UPPER(COALESCE(t.result, '')) = 'FAIL' THEN 1 ELSE 0 END) AS failed_total, "
    . "SUM(CASE WHEN {$currentCycle} THEN 1 ELSE 0 END) AS cycle_records "
    . "FROM tests AS t LEFT JOIN products AS p ON p.product_id = t.product_id";
$summary = $conn->query($summarySql)->fetch_assoc() ?: [];
$totalTests = (int) ($summary['total_tests'] ?? 0);
$openCurrent = (int) ($summary['open_current'] ?? 0);
$pendingCurrent = (int) ($summary['pending_current'] ?? 0);
$inProgressCurrent = (int) ($summary['in_progress_current'] ?? 0);
$completedCurrent = (int) ($summary['completed_current'] ?? 0);
$passedTotal = (int) ($summary['passed_total'] ?? 0);
$failedTotal = (int) ($summary['failed_total'] ?? 0);
$cycleRecords = (int) ($summary['cycle_records'] ?? 0);
$cycleDenominator = max(1, $cycleRecords);

$recordsStatement = $conn->prepare(
    'SELECT t.id, t.test_id, t.product_id, t.testing_date, t.result, t.status, t.cycle_number, '
    . 'p.product_name, p.status AS product_status, p.rework_cycle, p.is_archived, '
    . 'tt.test_name, tester.name AS tester_name '
    . 'FROM tests AS t '
    . 'LEFT JOIN products AS p ON p.product_id = t.product_id '
    . 'LEFT JOIN test_types AS tt ON tt.id = t.test_type_id '
    . 'LEFT JOIN testers AS tester ON tester.id = t.tester_id '
    . 'ORDER BY t.id DESC LIMIT 40'
);
$recordsStatement->execute();
$records = $recordsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$recordsStatement->close();

$pageTitle = 'Test status';
$pageEyebrow = 'LAB OPERATIONS';
$pageDescription = 'Review open work in the active product cycle, recorded outcomes, and the latest test records.';
$pageStylesheet = 'assets/css/pages/status-overview.css';
$pageActionHtml = app_can_access_route('new-test.php')
    ? '<a class="button button-primary" href="new-test.php"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Assign a test</a>'
    : '';
require __DIR__ . '/views/layouts/app_start.php';
?>

<section class="stats-grid status-metric-grid" aria-label="Test status totals">
    <?php
    $statusMetrics = [
        ['label' => 'Test records', 'value' => $totalTests, 'note' => 'All saved test records', 'icon' => 'fa-flask-vial', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Open this cycle', 'value' => $openCurrent, 'note' => 'Active assignments awaiting close', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Passed', 'value' => $passedTotal, 'note' => 'Recorded PASS outcomes', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'Failed', 'value' => $failedTotal, 'note' => 'Recorded FAIL outcomes', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
    foreach ($statusMetrics as $metric) {
        $statLabel = $metric['label'];
        $statValue = number_format((int) $metric['value']);
        $statNote = $metric['note'];
        $statIcon = $metric['icon'];
        $statTone = $metric['tone'];
        require __DIR__ . '/views/components/stat-card.php';
    }
    ?>
</section>

<section class="panel status-overview-panel" data-reveal>
    <header class="panel-header">
        <div><p class="eyebrow">ACTIVE PRODUCT CYCLE</p><h2>Current-cycle work</h2><p>Closed results stay in product history; the current view counts only the active product cycle.</p></div>
        <span class="status-pill is-info"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> <?php echo number_format($cycleRecords); ?> cycle records</span>
    </header>
    <div class="panel-body status-overview-body">
        <?php
        $progressRows = [
            ['label' => 'Pending', 'count' => $pendingCurrent, 'tone' => 'is-pending', 'color' => 'var(--theme-warning)'],
            ['label' => 'In progress', 'count' => $inProgressCurrent, 'tone' => 'is-progress', 'color' => 'var(--theme-info)'],
            ['label' => 'Completed', 'count' => $completedCurrent, 'tone' => 'is-complete', 'color' => 'var(--theme-success)'],
        ];
        foreach ($progressRows as $progressRow):
            $progressPercent = min(100, round(($progressRow['count'] / $cycleDenominator) * 100, 1));
        ?>
            <div class="status-progress-row">
                <div class="status-progress-meta"><span><?php echo htmlspecialchars($progressRow['label'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo number_format($progressRow['count']); ?><small> · <?php echo number_format($progressPercent, 1); ?>%</small></strong></div>
                <div class="status-progress-track" role="img" aria-label="<?php echo htmlspecialchars($progressRow['label'] . ': ' . number_format($progressRow['count']) . ' of ' . number_format($cycleRecords), ENT_QUOTES, 'UTF-8'); ?>"><span class="<?php echo htmlspecialchars($progressRow['tone'], ENT_QUOTES, 'UTF-8'); ?>" style="width: <?php echo $progressPercent; ?>%; --bar-color: <?php echo htmlspecialchars($progressRow['color'], ENT_QUOTES, 'UTF-8'); ?>"></span></div>
            </div>
        <?php endforeach; ?>
        <div class="status-overview-footnote"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Only current-cycle assignments on active products count as open. Archived and earlier-cycle work remains visible below as history.</span></div>
    </div>
</section>

<section class="panel status-records-panel" data-reveal>
    <header class="panel-header"><div><p class="eyebrow">LATEST RECORDS</p><h2>Testing status records</h2><p>Most recent 40 records, including closed and historical cycles.</p></div><a class="button button-quiet" href="testing.php">Browse all tests <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></header>
    <?php if ($records === []): ?>
        <?php $emptyTitle = 'No test records yet'; $emptyText = 'Once a test is assigned, its current status and final result will appear here.'; $emptyIcon = 'fa-flask-vial'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap"><table class="data-table status-records-table">
            <thead><tr><th>Test / product</th><th>Test type</th><th>Assigned Tester</th><th>Date</th><th>Cycle</th><th>Result</th><th>Record status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($records as $record): ?>
                <?php
                    $recordCycle = max(1, (int) ($record['cycle_number'] ?? 1));
                    $isCurrentCycle = (int) $record['is_archived'] !== 1 && $recordCycle === (int) $record['rework_cycle'] + 1;
                    $resultLabel = strtoupper((string) ($record['result'] ?? 'PENDING'));
                    if ($resultLabel === '') $resultLabel = 'PENDING';
                    $recordStatus = (string) ($record['status'] ?? 'Pending');
                ?>
                <tr>
                    <td><span class="table-primary"><?php echo htmlspecialchars((string) $record['test_id'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) $record['product_id'] . ' · ' . (string) ($record['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars((string) ($record['test_name'] ?? 'Test type'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($record['tester_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($record['testing_date'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="cycle-pill<?php echo $isCurrentCycle ? ' is-current' : ''; ?>">Cycle <?php echo $recordCycle; ?><?php echo $isCurrentCycle ? ' · Current' : ''; ?></span></td>
                    <td><?php $badgeLabel = $resultLabel; $badgeTone = $resultLabel; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                    <td><?php $badgeLabel = $recordStatus; $badgeTone = $recordStatus; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                    <td><a class="button button-quiet" href="test-details.php?id=<?php echo (int) $record['id']; ?>" aria-label="View <?php echo htmlspecialchars((string) $record['test_id'], ENT_QUOTES, 'UTF-8'); ?>">View <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
