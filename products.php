<?php
// Role-aware product catalogue with read access for lab staff and edit links only for lab leads.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);

require_once __DIR__ . '/models/Tester.php';
$search = trim((string) ($_GET['search'] ?? ''));
$role = (string) ($_SESSION['role'] ?? '');
$isTester = $role === 'Tester';
$testerProfile = $isTester ? Tester::findByUserId($conn, (int) ($_SESSION['user_id'] ?? 0)) : null;
$testerId = $testerProfile !== null ? (int) $testerProfile['id'] : 0;
$canManageProducts = app_can_access_route('add-product.php');
$canManageWorkflow = app_can_access_route('product-workflow.php');

$assignmentCondition = '';
$assignmentTypes = '';
$assignmentParams = [];
if ($isTester) {
    if ($testerId > 0) {
        $assignmentCondition = 'EXISTS (SELECT 1 FROM tests AS assigned_test WHERE assigned_test.product_id = p.product_id '
            . 'AND (assigned_test.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS assigned_participant '
            . 'WHERE assigned_participant.test_record_id = assigned_test.id AND assigned_participant.tester_id = ?)))';
        $assignmentTypes = 'ii';
        $assignmentParams = [$testerId, $testerId];
    } else {
        $assignmentCondition = '1 = 0';
    }
}

$statsSql = "SELECT COUNT(*) AS total,
    SUM(p.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest')) AS active_count,
    SUM(p.status IN ('Passed', 'CPRI Ready', 'Handed to CPRI')) AS passed_count,
    SUM(p.status = 'Failed - Re-manufacturing') AS rework_count
    FROM products AS p";
if ($assignmentCondition !== '') {
    $statsSql .= ' WHERE ' . $assignmentCondition;
}
$statsStatement = $conn->prepare($statsSql);
if ($assignmentTypes !== '') {
    $statsBindArguments = [$assignmentTypes];
    foreach (array_keys($assignmentParams) as $index) {
        $statsBindArguments[] = &$assignmentParams[$index];
    }
    call_user_func_array([$statsStatement, 'bind_param'], $statsBindArguments);
}
$statsStatement->execute();
$productStats = $statsStatement->get_result()->fetch_assoc() ?: [];
$statsStatement->close();

$conditions = [];
$types = '';
$params = [];
if ($assignmentCondition !== '') {
    $conditions[] = $assignmentCondition;
    $types .= $assignmentTypes;
    array_push($params, ...$assignmentParams);
}
if ($search !== '') {
    $conditions[] = '(p.product_id LIKE ? OR p.product_code LIKE ? OR p.product_name LIKE ? OR p.product_type LIKE ? OR p.revision LIKE ? OR p.status LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ssssss';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
$sql = 'SELECT p.id, p.product_id, p.product_code, p.product_name, p.product_type, p.revision, p.manufacturing_date, p.status '
    . 'FROM products AS p';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY p.id DESC LIMIT 250';
$statement = $conn->prepare($sql);
if ($types !== '') {
    $bindArguments = [$types];
    foreach (array_keys($params) as $index) {
        $bindArguments[] = &$params[$index];
    }
    call_user_func_array([$statement, 'bind_param'], $bindArguments);
}
$statement->execute();
$productRows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

$pageTitle = 'Products';
$pageEyebrow = 'PRODUCT REGISTER';
$pageDescription = $isTester
    ? 'Browse only products linked to test work assigned to your tester profile.'
    : 'Find product records, review identity and workflow status, and open the traceable test history.';
$actions = [];
if ($canManageWorkflow) {
    $actions[] = '<a class="button button-secondary" href="product-workflow.php"><i class="fa-solid fa-arrows-spin" aria-hidden="true"></i> Product workflow</a>';
}
if ($canManageProducts) {
    $actions[] = '<a class="button button-primary" href="add-product.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add product</a>';
}
$pageActionHtml = implode('', $actions);
require __DIR__ . '/views/layouts/app_start.php';
?>
<section class="stats-grid">
    <?php
    $metrics = [
        ['label' => 'Total products', 'value' => (int) ($productStats['total'] ?? 0), 'note' => 'Registered records', 'icon' => 'fa-cubes-stacked', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Active testing', 'value' => (int) ($productStats['active_count'] ?? 0), 'note' => 'Pending, testing or retest', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Passed / CPRI', 'value' => (int) ($productStats['passed_count'] ?? 0), 'note' => 'Passed or handoff state', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'In re-manufacture', 'value' => (int) ($productStats['rework_count'] ?? 0), 'note' => 'Requires controlled release', 'icon' => 'fa-arrows-rotate', 'tone' => 'var(--theme-danger)'],
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

<section class="panel" data-reveal>
    <form class="filter-bar" method="get" action="products.php">
        <label class="search-field" for="product-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="product-search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search Product ID, model, name or status"></label>
        <div class="action-row"><button class="button button-secondary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Search</button><?php if ($search !== ''): ?><a class="button button-quiet" href="products.php">Clear</a><?php endif; ?></div>
    </form>
    <?php if ($productRows === []): ?>
        <?php $emptyTitle = 'No product records found'; $emptyText = $search !== '' ? 'Try a different Product ID, exact model code or status.' : 'Product records will appear here after registration.'; $emptyIcon = 'fa-cubes-stacked'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Product ID</th><th>Product / model</th><th>Family</th><th>Revision</th><th>Manufactured</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($productRows as $product): ?>
                    <tr>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $product['product_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary">Model <?php echo htmlspecialchars((string) $product['product_code'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars((string) $product['product_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $product['revision'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $product['manufacturing_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php $badgeLabel = (string) $product['status']; $badgeTone = (string) $product['status']; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                        <td><div class="action-row"><a class="button button-quiet" href="product-details.php?id=<?php echo (int) $product['id']; ?>" aria-label="View product history"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><?php if ($canManageProducts): ?><a class="button button-quiet" href="edit-product.php?id=<?php echo (int) $product['id']; ?>" aria-label="Edit product"><i class="fa-solid fa-pen" aria-hidden="true"></i></a><?php endif; ?></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
