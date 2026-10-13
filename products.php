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
$canArchiveProducts = $role === 'Administrator';
$requestedView = (string) ($_GET['view'] ?? 'active');
if (!$canArchiveProducts && in_array($requestedView, ['archived', 'all'], true)) {
    RoleMiddleware::handle([]);
}
$view = $canArchiveProducts ? $requestedView : 'active';
if (!in_array($view, ['active', 'archived', 'all'], true)) {
    $view = 'active';
}
$success = '';
$error = '';

// Archive and restore keep every linked test and workflow record intact.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $archiveAction = (string) ($_POST['action'] ?? '');
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $returnView = (string) ($_POST['return_view'] ?? 'active');
    if (!in_array($returnView, ['active', 'archived', 'all'], true)) {
        $returnView = 'active';
    }
    if ($archiveAction === 'restore' && $returnView === 'archived') {
        $returnView = 'active';
    }
    if (!$canArchiveProducts) {
        http_response_code(403);
        $error = 'Only an Administrator can archive or restore product records.';
    } elseif (!in_array($archiveAction, ['archive', 'restore'], true) || $recordId < 1) {
        $error = 'Choose a valid product archive action.';
    } else {
        try {
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT product_id, is_archived FROM products WHERE id = ? FOR UPDATE');
            $lock->bind_param('i', $recordId);
            $lock->execute();
            $productToArchive = $lock->get_result()->fetch_assoc();
            $lock->close();
            if ($productToArchive === null) {
                throw new DomainException('The selected product no longer exists.');
            }
            $newArchivedState = $archiveAction === 'archive' ? 1 : 0;
            if ((int) $productToArchive['is_archived'] === $newArchivedState) {
                throw new DomainException($newArchivedState === 1 ? 'This product is already archived.' : 'This product is already active.');
            }
            $updateArchive = $conn->prepare('UPDATE products SET is_archived = ? WHERE id = ?');
            $updateArchive->bind_param('ii', $newArchivedState, $recordId);
            $updateArchive->execute();
            $updateArchive->close();
            $entityAction = $newArchivedState === 1 ? 'product_archived' : 'product_restored';
            $summary = $newArchivedState === 1
                ? 'Administrator archived the product from active lists; test history was retained.'
                : 'Administrator restored the product to active lists; test history was retained.';
            audit_write($conn, $entityAction, 'Product', (string) $productToArchive['product_id'], $summary, [
                'previously_archived' => (int) $productToArchive['is_archived'],
                'is_archived' => $newArchivedState,
            ]);
            $conn->commit();
            $_SESSION['products_flash_success'] = $newArchivedState === 1
                ? 'Product archived. Its product, test, and workflow history is still available.'
                : 'Product restored to the active list.';
            header('Location: products.php?view=' . rawurlencode($returnView), true, 303);
            exit;
        } catch (DomainException $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the validation message. */ }
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
            error_log('Product archive action failed: ' . $exception->getMessage());
            $error = 'The product archive action could not be saved. Please retry.';
        }
    }
}
$success = (string) ($_SESSION['products_flash_success'] ?? '');
unset($_SESSION['products_flash_success']);

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

$archiveCondition = $view === 'archived' ? 'p.is_archived = 1' : ($view === 'all' ? '1 = 1' : 'p.is_archived = 0');
$statsSql = "SELECT COUNT(*) AS total,
    SUM(p.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest')) AS active_count,
    SUM(p.status IN ('Passed', 'CPRI Ready', 'Handed to CPRI', 'CPRI Approved', 'Released to Market')) AS passed_count,
    SUM(p.status = 'Failed - Re-manufacturing') AS rework_count
    FROM products AS p WHERE {$archiveCondition}";
if ($assignmentCondition !== '') {
    $statsSql .= ' AND ' . $assignmentCondition;
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

$conditions = [$archiveCondition];
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
$sql = 'SELECT p.id, p.product_id, p.product_code, p.product_name, p.product_type, p.revision, p.manufacturing_date, p.status, p.is_archived '
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
    ? 'Browse only active products linked to test work assigned to your tester profile.'
    : ($view === 'archived'
        ? 'Review archived product identities without losing their linked tests and workflow history.'
        : 'Find active product records, review identity and workflow status, and open traceable test history.');
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
<?php if ($success !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<section class="stats-grid">
    <?php
    $metrics = [
        ['label' => 'Total products', 'value' => (int) ($productStats['total'] ?? 0), 'note' => 'Registered records', 'icon' => 'fa-cubes-stacked', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Active testing', 'value' => (int) ($productStats['active_count'] ?? 0), 'note' => 'Pending, testing or retest', 'icon' => 'fa-hourglass-half', 'tone' => 'var(--theme-warning)'],
        ['label' => 'Passed / CPRI', 'value' => (int) ($productStats['passed_count'] ?? 0), 'note' => 'Passed, approved or released', 'icon' => 'fa-circle-check', 'tone' => 'var(--theme-success)'],
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
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
        <label class="search-field" for="product-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="product-search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search Product ID, exact model, name or status"></label>
        <div class="action-row"><button class="button button-secondary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Search</button><?php if ($search !== ''): ?><a class="button button-quiet" href="products.php?view=<?php echo rawurlencode($view); ?>">Clear</a><?php endif; ?></div>
        <?php if ($canArchiveProducts): ?>
            <nav class="product-view-tabs" aria-label="Product archive views">
                <?php foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $tabValue => $tabLabel): ?>
                    <?php $tabQuery = http_build_query(array_filter(['view' => $tabValue, 'search' => $search], static fn ($value): bool => $value !== '')); ?>
                    <a class="button <?php echo $view === $tabValue ? 'button-secondary' : 'button-quiet'; ?>" href="products.php?<?php echo htmlspecialchars($tabQuery, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </form>
    <?php if ($productRows === []): ?>
        <?php $emptyTitle = 'No product records found'; $emptyText = $search !== '' ? 'Try a different Product ID, exact model code or status.' : 'Product records will appear here after registration.'; $emptyIcon = 'fa-cubes-stacked'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Product ID</th><th>Product / model</th><th>Family</th><th>Revision</th><th>Manufactured</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($productRows as $product): ?>
                    <tr>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $product['product_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary">Model <?php echo htmlspecialchars((string) $product['product_code'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars((string) $product['product_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $product['revision'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $product['manufacturing_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php $badgeLabel = (int) $product['is_archived'] === 1 ? 'Archived' : (string) $product['status']; $badgeTone = $badgeLabel; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                        <td><div class="action-row"><a class="button button-quiet" href="product-details.php?id=<?php echo (int) $product['id']; ?>" aria-label="View product history"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><?php if ($canManageProducts): ?><a class="button button-quiet" href="edit-product.php?id=<?php echo (int) $product['id']; ?>" aria-label="Edit product"><i class="fa-solid fa-pen" aria-hidden="true"></i></a><?php endif; ?>
                            <?php if ($canArchiveProducts): ?>
                                <form method="post" action="products.php?view=<?php echo rawurlencode($view); ?>" class="archive-product-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="record_id" value="<?php echo (int) $product['id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo (int) $product['is_archived'] === 1 ? 'restore' : 'archive'; ?>">
                                    <input type="hidden" name="return_view" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="button <?php echo (int) $product['is_archived'] === 1 ? 'button-secondary' : 'button-danger'; ?>" type="submit" aria-label="<?php echo (int) $product['is_archived'] === 1 ? 'Restore product' : 'Archive product'; ?>"><i class="fa-solid <?php echo (int) $product['is_archived'] === 1 ? 'fa-box-open' : 'fa-box-archive'; ?>" aria-hidden="true"></i><span><?php echo (int) $product['is_archived'] === 1 ? 'Restore' : 'Archive'; ?></span></button>
                                </form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
