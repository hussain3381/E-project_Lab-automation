<?php
// Manage lab departments used to route test types and work records.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);

$message = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $code = strtoupper(trim((string) ($_POST['department_code'] ?? '')));
    $name = trim((string) ($_POST['department_name'] ?? ''));

    if (!preg_match('/^[A-Z0-9_-]{2,16}$/', $code) || $name === '' || strlen($name) > 150) {
        $error = 'Enter a department code (2–16 letters/numbers) and a department name.';
    } else {
        try {
            $conn->begin_transaction();
            $statement = $conn->prepare('INSERT INTO departments (department_code, department_name) VALUES (?, ?)');
            $statement->bind_param('ss', $code, $name);
            $statement->execute();
            $departmentId = (int) $conn->insert_id;
            $statement->close();
            audit_write($conn, 'department_created', 'Department', (string) $departmentId, 'A laboratory department was added.', [
                'department_code' => $code,
                'department_name' => $name,
            ]);
            $conn->commit();
            $message = 'Department added.';
            $_POST = [];
        } catch (Throwable $exception) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackException) {
                /* Preserve the original failure. */
            }
            error_log('Lab Automation department write failed: ' . $exception->getMessage());
            $error = (int) $exception->getCode() === 1062
                ? 'That department code or name is already in use.'
                : 'Department could not be saved. Check the latest migration and try again.';
        }
    }
}

$departmentRows = $conn->query(
    'SELECT id, department_code, department_name, is_active FROM departments ORDER BY department_name'
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Departments';
$pageEyebrow = 'LAB CONFIGURATION';
$pageDescription = 'Manage the departments used to route each test type to the correct laboratory team.';
$pageActionHtml = '<a class="button button-secondary" href="test-types.php"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Test types</a>';
$pageStylesheet = 'assets/css/pages/departments.css';
$pageSuccess = $message;
$pageError = $error;
require __DIR__ . '/views/layouts/app_start.php';
?>
<section class="departments-card panel" data-reveal>
    <h2>Add department</h2>
    <form method="POST" action="departments.php">
        <!-- Shared CSRF protection applies to this form. -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <label for="department-code">Department code</label>
        <input id="department-code" name="department_code" maxlength="16" placeholder="e.g. ELEC" value="<?php echo htmlspecialchars((string) ($_POST['department_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        <label for="department-name">Department name</label>
        <input id="department-name" name="department_name" maxlength="150" placeholder="e.g. Electrical Testing" value="<?php echo htmlspecialchars((string) ($_POST['department_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        <button class="button button-primary" type="submit"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add department</button>
    </form>
</section>

<section class="departments-card panel" data-reveal>
    <h2>Department list</h2>
    <div class="departments-table-wrap">
        <table>
            <thead><tr><th>Code</th><th>Department</th><th>State</th></tr></thead>
            <tbody>
            <?php foreach ($departmentRows as $department): ?>
                <tr>
                    <td><?php echo htmlspecialchars($department['department_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="status-pill <?php echo (int) $department['is_active'] === 1 ? 'is-success' : 'is-warning'; ?>"><?php echo (int) $department['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($departmentRows === []): ?><tr><td colspan="3">No departments have been added yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
