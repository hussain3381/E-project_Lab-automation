<?php
// Administrator-only review of tracked account, product, test, and workflow events.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);

$search = trim((string) ($_GET['q'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? ''));
$since = trim((string) ($_GET['since'] ?? ''));
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = '(actor_name LIKE ? OR actor_role LIKE ? OR action LIKE ? OR entity LIKE ? OR summary LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sssss';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($roleFilter !== '') {
    $conditions[] = 'actor_role = ?';
    $types .= 's';
    $params[] = $roleFilter;
}
$date = DateTime::createFromFormat('!Y-m-d', $since);
if ($since !== '' && $date !== false && $date->format('Y-m-d') === $since) {
    $conditions[] = 'created_at >= ?';
    $types .= 's';
    $params[] = $since . ' 00:00:00';
}
$whereSql = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT id, actor_name, actor_role, action, entity, entity_id, summary, metadata, ip_address, created_at
        FROM audit_logs {$whereSql} ORDER BY id DESC LIMIT 250";
$statement = $conn->prepare($sql);
if ($types !== '') {
    $bind = [$types];
    foreach (array_keys($params) as $index) {
        $bind[] = &$params[$index];
    }
    call_user_func_array([$statement, 'bind_param'], $bind);
}
$statement->execute();
$events = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

$pageTitle = 'Activity log';
$pageEyebrow = 'ADMIN AUDIT';
$pageDescription = 'Recent tracked changes across accounts, records, products and laboratory workflow.';
$pageActionHtml = '<a class="button button-secondary" href="users.php"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> Manage accounts</a>';
require __DIR__ . '/views/layouts/app_start.php';
?>
<section class="panel" data-reveal>
    <form class="filter-bar" method="get" action="admin-activity.php">
        <label class="search-field" for="audit-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="audit-search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search people, actions or records"></label>
        <div class="action-row">
            <label class="sr-only" for="audit-role">Filter role</label>
            <select class="form-field-select" id="audit-role" name="role">
                <option value="">All roles</option>
                <?php foreach (['Administrator', 'Lab Manager', 'Tester', 'Quality Control', 'Public', 'System'] as $option): ?>
                    <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $roleFilter === $option ? ' selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="sr-only" for="audit-since">Since date</label>
            <input class="form-field-select" id="audit-since" name="since" type="date" value="<?php echo htmlspecialchars($since, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="button button-primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
            <?php if ($search !== '' || $roleFilter !== '' || $since !== ''): ?><a class="button button-quiet" href="admin-activity.php">Clear</a><?php endif; ?>
        </div>
    </form>
    <p class="form-hint">Password values, OAuth tokens and CSRF secrets are never written to this log.</p>
    <?php if ($events === []): ?>
        <?php $emptyTitle = 'No tracked activity found'; $emptyText = 'As account and laboratory changes are made, their audit entries will appear here.'; $emptyIcon = 'fa-clock-rotate-left'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><span class="table-primary"><?php echo htmlspecialchars(date('d M Y', strtotime((string) $event['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars(date('H:i:s', strtotime((string) $event['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $event['actor_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) $event['actor_role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="status-pill"><?php echo htmlspecialchars(str_replace('_', ' ', (string) $event['action']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="table-primary"><?php echo htmlspecialchars((string) $event['entity'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary"><?php echo htmlspecialchars((string) ($event['entity_id'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <span class="audit-summary"><?php echo htmlspecialchars((string) $event['summary'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php $metadataValue = json_decode((string) ($event['metadata'] ?? ''), true); $metadataPretty = is_array($metadataValue) ? json_encode($metadataValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : ''; ?>
                            <?php if ($metadataPretty !== ''): ?>
                                <details class="audit-metadata"><summary>View recorded fields</summary><pre><?php echo htmlspecialchars($metadataPretty, ENT_QUOTES, 'UTF-8'); ?></pre></details>
                            <?php endif; ?>
                            <?php if (!empty($event['ip_address'])): ?><span class="table-secondary">Source IP: <?php echo htmlspecialchars((string) $event['ip_address'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
