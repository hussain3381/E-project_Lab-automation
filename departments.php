<?php
// Manage the lab departments used to route test types and work records.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_roles(['Administrator', 'Lab Manager']);

$message = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $code = strtoupper(trim((string) ($_POST['department_code'] ?? '')));
    $name = trim((string) ($_POST['department_name'] ?? ''));

    if (!preg_match('/^[A-Z0-9_-]{2,16}$/', $code) || $name === '' || strlen($name) > 150) {
        $error = 'Enter a department code (2–16 letters/numbers) and a department name.';
    } else {
        try {
            $statement = $conn->prepare('INSERT INTO departments (department_code, department_name) VALUES (?, ?)');
            $statement->bind_param('ss', $code, $name);
            $statement->execute();
            $statement->close();
            $message = 'Department added.';
            $_POST = [];
        } catch (mysqli_sql_exception $exception) {
            error_log('Lab Automation department write failed: ' . $exception->getMessage());
            $error = 'That department code or name is already in use.';
        }
    }
}

$departmentRows = $conn->query(
    'SELECT id, department_code, department_name, is_active FROM departments ORDER BY department_name'
)->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <script>/* Restore the saved palette before painting. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Departments | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="assets/css/pages/departments.css">
</head>
<body class="departments-page">
    <main class="departments-shell">
        <header class="departments-header">
            <div><p class="departments-eyebrow">LAB CONFIGURATION</p><h1>Departments</h1><p>Manage the departments used to route each test type to the correct laboratory team.</p></div>
            <a href="test-types.php">Back to Test Types</a>
        </header>
        <?php if ($message !== ''): ?><div class="departments-alert departments-alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="departments-alert departments-alert--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <section class="departments-card">
            <h2>Add department</h2>
            <form method="POST" action="departments.php">
                <!-- Shared CSRF protection applies to this form. -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <label for="department-code">Department code</label>
                <input id="department-code" name="department_code" maxlength="16" placeholder="e.g. ELEC" required>
                <label for="department-name">Department name</label>
                <input id="department-name" name="department_name" maxlength="150" placeholder="e.g. Electrical Testing" required>
                <button type="submit">Add department</button>
            </form>
        </section>
        <section class="departments-card">
            <h2>Department list</h2>
            <div class="departments-table-wrap"><table>
                <thead><tr><th>Code</th><th>Department</th><th>State</th></tr></thead>
                <tbody>
                <?php foreach ($departmentRows as $department): ?>
                    <tr><td><?php echo htmlspecialchars($department['department_code'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $department['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td></tr>
                <?php endforeach; ?>
                <?php if ($departmentRows === []): ?><tr><td colspan="3">No departments have been added yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </main>
</body>
</html>
