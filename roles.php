<?php
// Read-only registry of application roles in the shared workspace shell.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);

try {
    $result = $conn->query(
        'SELECT r.id, r.role_name, r.description, r.is_system, '
        . 'COUNT(u.id) AS user_count, '
        . 'COALESCE(SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_users '
        . 'FROM roles AS r '
        . 'LEFT JOIN users AS u ON u.role_id = r.id '
        . 'GROUP BY r.id, r.role_name, r.description, r.is_system '
        . 'ORDER BY r.id'
    );
    $roleRows = $result->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $exception) {
    error_log('Role registry page failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Role registry is unavailable. Apply the latest database migration and try again.');
}

$roleAccess = require __DIR__ . '/config/roles.php';
$roleIcons = [
    'Administrator' => 'fa-shield-halved',
    'Lab Manager' => 'fa-user-gear',
    'Tester' => 'fa-flask-vial',
    'Quality Control' => 'fa-clipboard-check',
];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = 'Roles & access';
$pageEyebrow = 'SYSTEM ADMINISTRATION';
$pageDescription = 'Review the built-in roles, account assignments and the laboratory tools available to each team.';
$pageActionHtml = '<a class="button button-primary" href="users.php"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> Manage users</a>';
$pageStylesheet = 'assets/css/pages/roles.css';
require __DIR__ . '/views/layouts/app_start.php';
?>
<section class="roles-notice panel" aria-label="Security note" data-reveal>
    <strong>Access is enforced on the server.</strong>
    <span>These built-in roles control protected routes as well as navigation. Hiding a link alone is not the security boundary.</span>
</section>

<section class="roles-grid" aria-label="Registered roles" data-reveal>
    <?php foreach ($roleRows as $roleRow): ?>
        <?php
        $roleName = (string) $roleRow['role_name'];
        $details = $roleAccess[$roleName] ?? [
            'summary' => (string) $roleRow['description'],
            'access' => ['No custom route permissions are configured.'],
        ];
        $roleIcon = $roleIcons[$roleName] ?? 'fa-user-shield';
        ?>
        <article class="role-card">
            <div class="role-card-heading">
                <span class="role-icon" aria-hidden="true"><i class="fa-solid <?php echo $escape($roleIcon); ?>"></i></span>
                <span class="role-system-badge"><?php echo (int) $roleRow['is_system'] === 1 ? 'Built-in role' : 'Custom role'; ?></span>
            </div>
            <h2><?php echo $escape($roleName); ?></h2>
            <p class="role-description"><?php echo $escape($details['summary']); ?></p>
            <div class="role-counts">
                <span><strong><?php echo (int) $roleRow['user_count']; ?></strong> account<?php echo (int) $roleRow['user_count'] === 1 ? '' : 's'; ?></span>
                <span><strong><?php echo (int) $roleRow['active_users']; ?></strong> active</span>
            </div>
            <h3>Typical access</h3>
            <ul>
                <?php foreach ($details['access'] as $accessItem): ?>
                    <li><?php echo $escape($accessItem); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endforeach; ?>
</section>

<footer class="roles-footer" data-reveal>
    <span>Need to change someone’s access?</span>
    <a class="button button-secondary" href="users.php">Open user accounts <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
</footer>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
