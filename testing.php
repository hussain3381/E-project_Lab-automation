<?php
// Searchable role-scoped test history using the shared app shell and the same route policy as navigation.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/Tester.php';

$role = (string) ($_SESSION['role'] ?? 'Tester');
$isTester = $role === 'Tester';
$testerProfile = $isTester ? Tester::findByUserId($conn, (int) ($_SESSION['user_id'] ?? 0)) : null;
$testerId = $testerProfile !== null ? (int) $testerProfile['id'] : 0;
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$validStatuses = ['Pending', 'In Progress', 'Completed'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$conditions = [];
$types = '';
$params = [];
if ($isTester) {
    if ($testerId > 0) {
        $conditions[] = '(t.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS mine WHERE mine.test_record_id = t.id AND mine.tester_id = ?))';
        $types .= 'ii';
        $params[] = $testerId;
        $params[] = $testerId;
    } else {
        $conditions[] = '1 = 0';
    }
}
if ($search !== '') {
    $conditions[] = '(t.test_id LIKE ? OR t.product_id LIKE ? OR p.product_name LIKE ? OR tt.test_name LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($statusFilter !== '') {
    $conditions[] = 't.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
$whereSql = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

$departmentColumn = $conn->query(
    "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tests' AND COLUMN_NAME = 'department_id' LIMIT 1"
)->num_rows > 0;
$departmentSelect = $departmentColumn
    ? 'COALESCE(d.department_name, tt.department, \'Unassigned\') AS department_name'
    : 'COALESCE(tt.department, \'Unassigned\') AS department_name';
$departmentJoin = $departmentColumn ? 'LEFT JOIN departments AS d ON d.id = t.department_id' : '';

$sql = "SELECT t.id, t.test_id, t.product_id, t.testing_date, t.result, t.status, t.remarks,
        p.product_name, tt.test_name, {$departmentSelect},
        COALESCE((SELECT GROUP_CONCAT(DISTINCT person.name ORDER BY person.name SEPARATOR ', ')
            FROM test_participants AS part
            INNER JOIN testers AS person ON person.id = part.tester_id
            WHERE part.test_record_id = t.id), primary_tester.name, 'Not assigned') AS tester_names
    FROM tests AS t
    LEFT JOIN products AS p ON p.product_id = t.product_id
    LEFT JOIN test_types AS tt ON tt.id = t.test_type_id
    {$departmentJoin}
    LEFT JOIN testers AS primary_tester ON primary_tester.id = t.tester_id
    {$whereSql}
    ORDER BY t.testing_date DESC, t.id DESC
    LIMIT 250";
$statement = $conn->prepare($sql);
if ($types !== '') {
    $bindArguments = [$types];
    foreach (array_keys($params) as $index) {
        $bindArguments[] = &$params[$index];
    }
    call_user_func_array([$statement, 'bind_param'], $bindArguments);
}
$statement->execute();
$rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

$counts = ['total' => count($rows), 'pending' => 0, 'passed' => 0, 'failed' => 0];
foreach ($rows as $row) {
    $resultValue = strtoupper((string) $row['result']);
    if ($resultValue === 'PASS') {
        $counts['passed']++;
    } elseif ($resultValue === 'FAIL') {
        $counts['failed']++;
    } else {
        $counts['pending']++;
    }
}

$pageTitle = 'Testing records';
$pageEyebrow = $role === 'Quality Control' ? 'QUALITY REVIEW' : ($role === 'Tester' ? 'MY ASSIGNED WORK' : 'LAB OPERATIONS');
$pageDescription = $role === 'Tester'
    ? 'Review only test records assigned to your linked tester profile.'
    : 'Search test IDs and Product IDs, review recorded outcomes and open a traceable detail history.';
$pageActionHtml = app_can_access_route('new-test.php')
    ? '<a class="button button-primary" href="new-test.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Record test</a>'
    : '<a class="button button-secondary" href="reports.php"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Review reports</a>';
require __DIR__ . '/views/layouts/app_start.php';
?>
<section class="stats-grid">
    <?php
    $metrics = [
        ['label' => 'Records shown', 'value' => $counts['total'], 'note' => 'After current filters', 'icon' => 'fa-flask-vial', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Pending review', 'value' => $counts['pending'], 'note' => 'No final PASS / FAIL', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Passed', 'value' => $counts['passed'], 'note' => 'Recorded PASS outcomes', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'Failed', 'value' => $counts['failed'], 'note' => 'Follow re-manufacture workflow', 'icon' => 'fa-triangle-exclamation', 'tone' => 'var(--theme-danger)'],
    ];
    foreach ($metrics as $metric) {
        $statLabel = $metric['label'];
        $statValue = number_format((int) $metric['value']);
        $statNote = $metric['note'];
        $statIcon = $metric['icon'];
        $statTone = $metric['tone'];
        require __DIR__ . '/views/components/stat-card.php';
    }
    ?>
</section>

<?php if ($isTester && $testerProfile === null): ?>
    <div class="alert alert-error" role="alert">Your login is not linked to a tester profile. Contact a Lab Manager before submitting or reviewing assigned test records.</div>
<?php endif; ?>

<section class="panel" data-reveal>
    <form class="filter-bar" method="get" action="testing.php">
        <label class="search-field" for="test-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="test-search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search Test ID, Product ID, product or test type"></label>
        <div class="action-row">
            <label class="sr-only" for="status-filter">Filter by status</label>
            <select class="form-field-select" id="status-filter" name="status">
                <option value="">All statuses</option>
                <?php foreach ($validStatuses as $statusOption): ?><option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $statusFilter === $statusOption ? ' selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
            </select>
            <button class="button button-secondary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
            <?php if ($search !== '' || $statusFilter !== ''): ?><a class="button button-quiet" href="testing.php">Clear</a><?php endif; ?>
        </div>
    </form>
    <?php if ($rows === []): ?>
        <?php $emptyTitle = 'No matching test records'; $emptyText = $isTester ? 'Assigned tests will appear here once a Lab Manager adds your tester profile.' : 'Try a different search or create the first test record.'; $emptyIcon = 'fa-magnifying-glass'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Test / product</th><th>Test type</th><th>Tester(s)</th><th>Department</th><th>Date</th><th>Result</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $row['test_id'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) $row['product_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) ($row['test_name'] ?? 'Test'), ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars((string) $row['tester_names'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['testing_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php $badgeLabel = (string) $row['result']; $badgeTone = (string) $row['result']; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                        <td><?php $badgeLabel = (string) $row['status']; $badgeTone = (string) $row['status']; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                        <td><div class="action-row">
                            <?php if ($isTester && strtoupper((string) $row['result']) === 'PENDING' && in_array(strtolower((string) $row['status']), ['pending', 'in progress', 'testing in progress'], true)): ?><a class="button button-primary" href="complete-test.php?id=<?php echo (int) $row['id']; ?>">Record result</a><?php endif; ?>
                            <a class="button button-quiet" href="test-details.php?id=<?php echo (int) $row['id']; ?>" aria-label="Open test details"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
