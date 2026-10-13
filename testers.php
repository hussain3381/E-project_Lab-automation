<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);

$message = '';
$error = '';
$actorRole = (string) ($_SESSION['role'] ?? '');
$testerRoleId = (int) ($conn->query("SELECT id FROM roles WHERE role_name = 'Tester' LIMIT 1")->fetch_assoc()['id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? (isset($_POST['add_tester']) ? 'create' : ''));
    $profileId = filter_var($_POST['tester_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $name = trim((string) ($_POST['name'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $designation = trim((string) ($_POST['designation'] ?? ''));
    $departmentValue = $department !== '' ? $department : null;
    $designationValue = $designation !== '' ? $designation : null;

    try {
        if ($action === 'create') {
            if ($name === '' || strlen($name) > 120 || strlen($department) > 150 || strlen($designation) > 100) {
                throw new InvalidArgumentException('Enter a tester name (maximum 120 characters) and valid profile details.');
            }
            $conn->begin_transaction();
            $duplicate = $conn->prepare('SELECT id FROM testers WHERE LOWER(name) = LOWER(?) AND department <=> ? LIMIT 1 FOR UPDATE');
            $duplicate->bind_param('ss', $name, $departmentValue);
            $duplicate->execute();
            $duplicateFound = $duplicate->get_result()->num_rows > 0;
            $duplicate->close();
            if ($duplicateFound) {
                throw new DomainException('A tester with this exact name and department already exists. Edit or reactivate the existing profile instead of creating a duplicate.');
            }
            $insert = $conn->prepare('INSERT INTO testers (name, department, designation, is_active) VALUES (?, ?, ?, 1)');
            $insert->bind_param('sss', $name, $departmentValue, $designationValue);
            $insert->execute();
            $newProfileId = (int) $conn->insert_id;
            $insert->close();
            audit_write($conn, 'tester_profile_created', 'Tester', (string) $newProfileId, 'An unlinked laboratory Tester profile was added.', [
                'name' => $name,
                'department' => $departmentValue,
                'designation' => $designationValue,
            ]);
            $conn->commit();
            $message = 'Tester profile added.';
        } elseif ($action === 'update_profile' && $profileId > 0) {
            if ($name === '' || strlen($name) > 120 || strlen($department) > 150 || strlen($designation) > 100) {
                throw new InvalidArgumentException('Enter a tester name (maximum 120 characters) and valid profile details.');
            }
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT id, user_id, name, department, designation FROM testers WHERE id = ? FOR UPDATE');
            $lock->bind_param('i', $profileId);
            $lock->execute();
            $before = $lock->get_result()->fetch_assoc();
            $lock->close();
            if ($before === null) {
                throw new DomainException('The selected Tester profile no longer exists.');
            }
            $linkedUserId = (int) ($before['user_id'] ?? 0);
            if ($linkedUserId > 0 && $actorRole !== 'Administrator' && $name !== (string) $before['name']) {
                throw new DomainException('Only an Administrator can change a linked account name. Manage this identity in User Accounts.');
            }
            $update = $conn->prepare('UPDATE testers SET name = ?, department = ?, designation = ? WHERE id = ?');
            $update->bind_param('sssi', $name, $departmentValue, $designationValue, $profileId);
            $update->execute();
            $update->close();
            if ($linkedUserId > 0 && $actorRole === 'Administrator' && $name !== (string) $before['name']) {
                $accountUpdate = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
                $accountUpdate->bind_param('si', $name, $linkedUserId);
                $accountUpdate->execute();
                $accountUpdate->close();
                if ($linkedUserId === (int) ($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['name'] = $name;
                }
            }
            audit_write($conn, 'tester_profile_updated', 'Tester', (string) $profileId, 'A Tester profile was corrected without changing linked test assignments.', [
                'linked_user_id' => $linkedUserId > 0 ? $linkedUserId : null,
                'name_before' => (string) $before['name'],
                'name_after' => $name,
                'department_before' => (string) ($before['department'] ?? ''),
                'department_after' => $departmentValue,
                'designation_before' => (string) ($before['designation'] ?? ''),
                'designation_after' => $designationValue,
            ]);
            $conn->commit();
            $message = 'Tester profile updated. Existing test assignments remain linked.';
        } elseif (in_array($action, ['deactivate', 'reactivate'], true) && $profileId > 0) {
            if ($actorRole !== 'Administrator') {
                throw new DomainException('Only an Administrator can deactivate or reactivate Tester access.');
            }
            $newActive = $action === 'reactivate' ? 1 : 0;
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT t.id, t.user_id, t.name, t.is_active, COALESCE(r.role_name, u.role) AS user_role FROM testers AS t LEFT JOIN users AS u ON u.id = t.user_id LEFT JOIN roles AS r ON r.id = u.role_id WHERE t.id = ? FOR UPDATE');
            $lock->bind_param('i', $profileId);
            $lock->execute();
            $profile = $lock->get_result()->fetch_assoc();
            $lock->close();
            if ($profile === null) {
                throw new DomainException('The selected Tester profile no longer exists.');
            }
            if ($newActive === 1 && (int) ($profile['user_id'] ?? 0) > 0 && (string) ($profile['user_role'] ?? '') !== 'Tester') {
                throw new DomainException('This profile is linked to an account with a different role. Reassign the account to Tester in User Accounts first.');
            }
            if ((int) $profile['is_active'] === $newActive) {
                throw new DomainException($newActive === 1 ? 'This Tester profile is already active.' : 'This Tester profile is already inactive.');
            }
            $profileUpdate = $conn->prepare('UPDATE testers SET is_active = ? WHERE id = ?');
            $profileUpdate->bind_param('ii', $newActive, $profileId);
            $profileUpdate->execute();
            $profileUpdate->close();
            $linkedUserId = (int) ($profile['user_id'] ?? 0);
            if ($linkedUserId > 0) {
                $accountUpdate = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND (role_id = ? OR (role_id IS NULL AND role = 'Tester'))");
                $accountUpdate->bind_param('iii', $newActive, $linkedUserId, $testerRoleId);
                $accountUpdate->execute();
                $accountChanged = $accountUpdate->affected_rows === 1;
                $accountUpdate->close();
                if ($accountChanged) {
                    audit_write($conn, $newActive === 1 ? 'user_reactivated' : 'user_deactivated', 'User', (string) $linkedUserId, $newActive === 1 ? 'Administrator reactivated the linked Tester account.' : 'Administrator deactivated the linked Tester account.', [
                        'tester_profile_id' => $profileId,
                    ]);
                }
            }
            audit_write($conn, $newActive === 1 ? 'tester_profile_reactivated' : 'tester_profile_deactivated', 'Tester', (string) $profileId, $newActive === 1 ? 'Tester profile restored; test history was retained.' : 'Tester profile deactivated; test history was retained.', [
                'linked_user_id' => $linkedUserId > 0 ? $linkedUserId : null,
            ]);
            $conn->commit();
            $message = $newActive === 1 ? 'Tester profile restored; historical assignments were kept.' : 'Tester profile deactivated; historical assignments were kept.';
        } else {
            throw new InvalidArgumentException('Choose a valid Tester profile action.');
        }
    } catch (DomainException | InvalidArgumentException $exception) {
        try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the validation message. */ }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
        error_log('Tester profile update failed: ' . $exception->getMessage());
        $error = (int) $exception->getCode() === 1062
            ? 'That linked account or profile already exists.'
            : 'Tester profile could not be saved. Please retry.';
    }
}

/* Keep active and inactive staff visible; never delete linked history. */
$countRow = $conn->query('SELECT COUNT(*) AS total, SUM(is_active = 1) AS active_total FROM testers')->fetch_assoc() ?: [];
$total_testers = (int) ($countRow['total'] ?? 0);
$active_testers = (int) ($countRow['active_total'] ?? 0);
$departmentRow = $conn->query("SELECT COUNT(DISTINCT department) AS total FROM testers WHERE is_active = 1 AND department IS NOT NULL AND department <> ''")->fetch_assoc() ?: [];
$departments = (int) ($departmentRow['total'] ?? 0);
$testers = $conn->query('SELECT * FROM testers ORDER BY is_active DESC, name ASC, id ASC');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Testers | Lab Automation</title>

<link rel="stylesheet" href="assets/css/pages/testers.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <div class="header">

        <div>

            <h1>Testers Management</h1>

            <p>Manage laboratory testers and testing staff.</p>

        </div>


        <a href="#addTester" class="add-btn">
            + Add Tester
        </a>

    </div>


    <?php if ($message != "") { ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <?php if ($error != "") { ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <!-- STATS -->

    <section class="stats">

        <div class="stat-card">

            <span>Total Testers</span>

            <h2>
                <?php echo $total_testers; ?>
            </h2>

        </div>


        <div class="stat-card">
            <span>Active tester profiles</span>
            <h2><?php echo $active_testers; ?></h2>
        </div>

        <div class="stat-card">

            <span>Testing Departments</span>

            <h2>
                <?php echo $departments; ?>
            </h2>

        </div>

    </section>



    <!-- ADD TESTER FORM -->

    <section class="form-card" id="addTester">

        <h2>Add New Tester</h2>

        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-grid">

                <div class="field">

                    <label>Tester Name *</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter tester name"
                        required
                    >

                </div>


                <div class="field">

                    <label>Department</label>

                    <select name="department">

                        <option value="">Select Department</option>

                        <option value="Electrical Testing">
                            Electrical Testing
                        </option>

                        <option value="Safety Testing">
                            Safety Testing
                        </option>

                        <option value="Performance Testing">
                            Performance Testing
                        </option>

                        <option value="Quality Control">
                            Quality Control
                        </option>

                    </select>

                </div>


                <div class="field">

                    <label>Designation</label>

                    <input
                        type="text"
                        name="designation"
                        placeholder="e.g. Senior Tester"
                    >

                </div>

            </div>


            <div class="form-submit">

                <button type="submit" name="add_tester">
                    Save Tester
                </button>

            </div>

        </form>

    </section>



    <!-- TESTERS TABLE -->

    <section class="table-card">

        <div class="table-header">

            <h2>Laboratory Testers</h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Tester Name</th>

                        <th>Department</th>

                        <th>Designation</th>

                        <th>Access</th>

                        <th>Manage</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if ($testers && mysqli_num_rows($testers) > 0) {

                    $count = 1;

                    while ($row = mysqli_fetch_assoc($testers)) {

                ?>

                    <tr>

                        <td>
                            <?php echo $count++; ?>
                        </td>

                        <td class="tester-name">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>

                        <td class="department">

                            <?php

                            echo htmlspecialchars(
                                $row['department'] ?: '—'
                            );

                            ?>

                        </td>

                        <td class="designation">

                            <?php

                            echo htmlspecialchars(
                                $row['designation'] ?: '—'
                            );

                            ?>

                        </td>

                        <td>
                            <span class="status-pill <?php echo (int) $row['is_active'] === 1 ? 'is-success' : 'is-danger'; ?>"><?php echo (int) $row['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span>
                            <?php if ((int) ($row['user_id'] ?? 0) > 0): ?><span class="table-secondary">Linked account</span><?php else: ?><span class="table-secondary">Profile only</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="action-row tester-manage-actions">
                                <details class="tester-edit-details">
                                    <summary class="button button-secondary"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit</summary>
                                    <form method="post" action="testers.php" class="tester-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="update_profile">
                                        <input type="hidden" name="tester_id" value="<?php echo (int) $row['id']; ?>">
                                        <div class="form-field"><label>Tester name</label><input name="name" value="<?php echo htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120"<?php echo (int) ($row['user_id'] ?? 0) > 0 && $actorRole !== 'Administrator' ? ' readonly' : ''; ?> required></div>
                                        <div class="form-field"><label>Department</label><input name="department" value="<?php echo htmlspecialchars((string) ($row['department'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="150"></div>
                                        <div class="form-field"><label>Designation</label><input name="designation" value="<?php echo htmlspecialchars((string) ($row['designation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="100"></div>
                                        <?php if ((int) ($row['user_id'] ?? 0) > 0 && $actorRole !== 'Administrator'): ?><p class="form-hint">Account name is managed by an Administrator in User Accounts.</p><?php endif; ?>
                                        <button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile</button>
                                    </form>
                                </details>
                                <?php if ($actorRole === 'Administrator'): ?>
                                    <form method="post" action="testers.php" class="tester-state-form"<?php echo (int) $row['is_active'] === 1 ? ' onsubmit="return confirm(&quot;Deactivate this profile and its linked Tester login? Existing test history will be retained.&quot;);"' : ''; ?>>
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="<?php echo (int) $row['is_active'] === 1 ? 'deactivate' : 'reactivate'; ?>">
                                        <input type="hidden" name="tester_id" value="<?php echo (int) $row['id']; ?>">
                                        <button class="button <?php echo (int) $row['is_active'] === 1 ? 'button-danger' : 'button-secondary'; ?>" type="submit"><i class="fa-solid <?php echo (int) $row['is_active'] === 1 ? 'fa-user-slash' : 'fa-user-check'; ?>" aria-hidden="true"></i> <?php echo (int) $row['is_active'] === 1 ? 'Deactivate' : 'Reactivate'; ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td colspan="6" class="empty">

                            No testers found.

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


</body>

</html>
