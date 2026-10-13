<?php
// Administrator account center: edit identities, assign built-in roles, and deactivate without erasing history.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);

/** Link one unambiguous, active email profile or create a single profile without stealing a linked identity. */
function admin_sync_tester_profile(mysqli $connection, int $userId, string $name, ?string $email, int $isActive): int
{
    if ($email !== null && trim($email) !== '') {
        $lookup = $connection->prepare('SELECT id, user_id, is_active FROM testers WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 3 FOR UPDATE');
        $lookup->bind_param('s', $email);
        $lookup->execute();
        $matches = $lookup->get_result()->fetch_all(MYSQLI_ASSOC);
        $lookup->close();

        $unlinkedMatches = array_values(array_filter($matches, static fn (array $profile): bool => $profile['user_id'] === null));
        $hasLinkedMatch = count($matches) !== count($unlinkedMatches);
        if ($hasLinkedMatch) {
            throw new DomainException('This email is already attached to another Tester account. Resolve the linked account before assigning it again.');
        }
        if (count($unlinkedMatches) > 1) {
            throw new DomainException('Multiple unlinked Tester profiles use this email. Resolve that duplicate before assigning the account role.');
        }
        if (count($unlinkedMatches) === 1) {
            if ((int) $unlinkedMatches[0]['is_active'] !== 1) {
                throw new DomainException('An inactive Tester profile uses this email. Review or reactivate that profile before linking it.');
            }
            $profileId = (int) $unlinkedMatches[0]['id'];
            $link = $connection->prepare('UPDATE testers SET user_id = ?, name = ?, email = ?, is_active = ? WHERE id = ? AND user_id IS NULL');
            $link->bind_param('issii', $userId, $name, $email, $isActive, $profileId);
            $link->execute();
            $didLink = $link->affected_rows === 1;
            $link->close();
            if (!$didLink) {
                throw new DomainException('The existing Tester profile could not be linked safely. Reload and try again.');
            }
            return $profileId;
        }
    }

    $insert = $connection->prepare("INSERT INTO testers (user_id, name, department, designation, email, is_active) VALUES (?, ?, NULL, 'Lab Tester', ?, ?)");
    $insert->bind_param('issi', $userId, $name, $email, $isActive);
    $insert->execute();
    $profileId = (int) $connection->insert_id;
    $insert->close();
    return $profileId;
}

$roleOptions = [];
$roleIds = [];
$roleRows = $conn->query('SELECT id, role_name, description FROM roles ORDER BY id')->fetch_all(MYSQLI_ASSOC);
foreach ($roleRows as $roleRow) {
    $roleName = (string) $roleRow['role_name'];
    $roleOptions[$roleName] = (string) $roleRow['description'];
    $roleIds[$roleName] = (int) $roleRow['id'];
}
$success = '';
$error = '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole = (string) ($_SESSION['role'] ?? 'Administrator');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? ''));
        if ($name === '' || strlen($name) > 120) {
            $error = 'Enter a name of 1–120 characters.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            $error = 'Username must be 3–40 letters, numbers, dots, underscores, or hyphens.';
        } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
            $error = 'Enter a valid email address or leave it blank.';
        } elseif (strlen($password) < 12) {
            $error = 'New accounts need a password of at least 12 characters.';
        } elseif (!array_key_exists($role, $roleOptions)) {
            $error = 'Select one of the four registered system roles.';
        } else {
            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if (!is_string($passwordHash)) {
                    throw new RuntimeException('Could not secure the account password.');
                }
                $conn->begin_transaction();
                $emailValue = $email !== '' ? $email : null;
                $roleId = $roleIds[$role];
                $insert = $conn->prepare('INSERT INTO users (name, username, email, password, role, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $insert->bind_param('sssssi', $name, $username, $emailValue, $passwordHash, $role, $roleId);
                $insert->execute();
                $newUserId = (int) $conn->insert_id;
                $insert->close();

                $testerProfileId = $role === 'Tester'
                    ? admin_sync_tester_profile($conn, $newUserId, $name, $emailValue, 1)
                    : null;
                audit_write($conn, 'user_created', 'User', (string) $newUserId, 'Administrator created a ' . $role . ' account.', [
                    'username' => $username,
                    'role' => $role,
                    'email_provided' => $emailValue !== null,
                    'tester_profile_id' => $testerProfileId,
                ]);
                $conn->commit();
                $success = 'Account created. ' . ($role === 'Tester' ? 'A linked tester profile was also created or an exact-email profile was reused.' : '');
            } catch (DomainException $exception) {
                try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the validation message. */ }
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
                error_log('Admin account creation failed: ' . $exception->getMessage());
                $error = (int) $exception->getCode() === 1062
                    ? 'That username or email is already in use.'
                    : 'Account could not be created. Check the fields and try again.';
            }
        }
    } elseif ($action === 'update' && $targetId > 0) {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = trim((string) ($_POST['role'] ?? ''));
        $isActive = (int) (($_POST['is_active'] ?? '0') === '1');
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if ($name === '' || strlen($name) > 120) {
            $error = 'Enter a name of 1–120 characters.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            $error = 'Username must be 3–40 letters, numbers, dots, underscores, or hyphens.';
        } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
            $error = 'Enter a valid email address or leave it blank.';
        } elseif (!array_key_exists($role, $roleOptions)) {
            $error = 'Select one of the four registered system roles.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 12) {
            $error = 'A replacement password must be at least 12 characters.';
        } elseif ($targetId === $currentUserId && ($role !== $currentRole || $isActive !== 1)) {
            $error = 'For safety, you cannot change your own role or deactivate your current account.';
        } else {
            try {
                $conn->begin_transaction();
                $lock = $conn->prepare('SELECT u.id, u.name, u.username, u.email, COALESCE(r.role_name, u.role) AS role, u.is_active FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id WHERE u.id = ? FOR UPDATE');
                $lock->bind_param('i', $targetId);
                $lock->execute();
                $before = $lock->get_result()->fetch_assoc();
                $lock->close();
                if ($before === null) {
                    throw new DomainException('The selected account no longer exists.');
                }
                if ((string) $before['role'] === 'Administrator' && (int) $before['is_active'] === 1 && ($role !== 'Administrator' || $isActive !== 1)) {
                    $administratorRoleId = $roleIds['Administrator'] ?? 0;
                    $activeAdminRows = $conn->prepare("SELECT id FROM users WHERE is_active = 1 AND (role_id = ? OR (role_id IS NULL AND role = 'Administrator')) FOR UPDATE");
                    $activeAdminRows->bind_param('i', $administratorRoleId);
                    $activeAdminRows->execute();
                    $activeAdminResult = $activeAdminRows->get_result();
                    $activeAdmins = $activeAdminResult->num_rows;
                    $activeAdminResult->free();
                    $activeAdminRows->close();
                    if ($activeAdmins <= 1) {
                        throw new DomainException('The last active Administrator cannot be demoted or deactivated.');
                    }
                }
                $emailValue = $email !== '' ? $email : null;
                $roleId = $roleIds[$role];
                $duplicate = $conn->prepare('SELECT id FROM users WHERE id <> ? AND (username = ? OR (email IS NOT NULL AND LOWER(TRIM(email)) = ?)) LIMIT 1');
                $duplicate->bind_param('iss', $targetId, $username, $emailValue);
                $duplicate->execute();
                $hasDuplicate = $duplicate->get_result()->num_rows > 0;
                $duplicate->close();
                if ($hasDuplicate) {
                    throw new DomainException('That username or email belongs to another account.');
                }

                if ($newPassword !== '') {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    if (!is_string($passwordHash)) {
                        throw new RuntimeException('Could not secure the replacement password.');
                    }
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, password = ?, role = ?, role_id = ?, is_active = ? WHERE id = ?');
                    $update->bind_param('sssssiii', $name, $username, $emailValue, $passwordHash, $role, $roleId, $isActive, $targetId);
                } else {
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, role = ?, role_id = ?, is_active = ? WHERE id = ?');
                    $update->bind_param('ssssiii', $name, $username, $emailValue, $role, $roleId, $isActive, $targetId);
                }
                $update->execute();
                $update->close();

                $profileLookup = $conn->prepare('SELECT id, email FROM testers WHERE user_id = ? LIMIT 1');
                $profileLookup->bind_param('i', $targetId);
                $profileLookup->execute();
                $profile = $profileLookup->get_result()->fetch_assoc();
                $profileLookup->close();
                $syncedTesterProfileId = null;
                if ($role === 'Tester') {
                    if ($profile !== null) {
                        if ($emailValue !== null && $emailValue !== (string) ($profile['email'] ?? '')) {
                            $conflictingProfile = $conn->prepare('SELECT id FROM testers WHERE id <> ? AND LOWER(TRIM(email)) = LOWER(?) LIMIT 1 FOR UPDATE');
                            $profileIdForEmail = (int) $profile['id'];
                            $conflictingProfile->bind_param('is', $profileIdForEmail, $emailValue);
                            $conflictingProfile->execute();
                            $hasProfileEmailConflict = $conflictingProfile->get_result()->num_rows > 0;
                            $conflictingProfile->close();
                            if ($hasProfileEmailConflict) {
                                throw new DomainException('Another Tester profile already uses this email. Resolve that profile before changing the account email.');
                            }
                        }
                        $profileUpdate = $conn->prepare('UPDATE testers SET name = ?, email = ?, is_active = ? WHERE user_id = ?');
                        $profileUpdate->bind_param('ssii', $name, $emailValue, $isActive, $targetId);
                        $profileUpdate->execute();
                        $profileUpdate->close();
                        $syncedTesterProfileId = (int) $profile['id'];
                    } else {
                        $syncedTesterProfileId = admin_sync_tester_profile($conn, $targetId, $name, $emailValue, $isActive);
                    }
                } elseif ($profile !== null) {
                    $profileDisable = $conn->prepare('UPDATE testers SET is_active = 0 WHERE user_id = ?');
                    $profileDisable->bind_param('i', $targetId);
                    $profileDisable->execute();
                    $profileDisable->close();
                }

                $userAuditAction = (int) $before['is_active'] === 1 && $isActive === 0
                    ? 'user_deactivated'
                    : ((int) $before['is_active'] === 0 && $isActive === 1
                        ? 'user_reactivated'
                        : ((string) $before['role'] !== $role ? 'user_role_changed' : 'user_updated'));
                audit_write($conn, $userAuditAction, 'User', (string) $targetId, 'Administrator updated an account and access assignment.', [
                    'username_before' => (string) $before['username'],
                    'username_after' => $username,
                    'role_before' => (string) $before['role'],
                    'role_after' => $role,
                    'active_before' => (int) $before['is_active'],
                    'active_after' => $isActive,
                    'password_reset' => $newPassword !== '',
                    'tester_profile_id' => $syncedTesterProfileId,
                ]);
                $conn->commit();
                $success = 'Account updated; role-based access and Tester profile were synchronized.';
                if ($targetId === $currentUserId) {
                    $_SESSION['name'] = $name;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                }
            } catch (DomainException $exception) {
                try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the validation message. */ }
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
                error_log('Admin account update failed: ' . $exception->getMessage());
                $error = (int) $exception->getCode() === 1062
                    ? 'That username or email is already in use.'
                    : 'Account could not be updated. Please retry.';
            }
        }
    } else {
        $error = 'Unknown account action.';
    }
}

$stats = $conn->query("SELECT COUNT(*) AS total, SUM(u.is_active = 1) AS active, SUM(u.is_active = 0) AS inactive, SUM(CASE WHEN COALESCE(r.role_name, u.role) = 'Tester' AND u.is_active = 1 THEN 1 ELSE 0 END) AS testers FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id")->fetch_assoc() ?: [];
$users = $conn->query('SELECT u.id, u.name, u.username, u.email, COALESCE(r.role_name, u.role) AS role, u.role_id, u.is_active, u.created_at FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id ORDER BY u.is_active DESC, u.name ASC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'User accounts';
$pageEyebrow = 'ACCESS ADMINISTRATION';
$pageDescription = 'Edit account details, assign one of the four built-in roles, and deactivate access without deleting lab history.';
$pageActionHtml = '<a class="button button-secondary" href="admin-activity.php"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Activity log</a>';
require __DIR__ . '/views/layouts/app_start.php';
?>
<?php if ($success !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<section class="stats-grid">
    <?php
    $metrics = [
        ['label' => 'All accounts', 'value' => (int) ($stats['total'] ?? 0), 'note' => 'System identities', 'icon' => 'fa-users', 'tone' => 'var(--theme-accent)'],
        ['label' => 'Active access', 'value' => (int) ($stats['active'] ?? 0), 'note' => 'Can sign in now', 'icon' => 'fa-user-check', 'tone' => 'var(--theme-success)'],
        ['label' => 'Tester seats', 'value' => (int) ($stats['testers'] ?? 0), 'note' => 'Linked profiles synced', 'icon' => 'fa-flask-vial', 'tone' => 'var(--theme-info)'],
        ['label' => 'Deactivated', 'value' => (int) ($stats['inactive'] ?? 0), 'note' => 'History retained', 'icon' => 'fa-user-slash', 'tone' => 'var(--theme-warning)'],
    ];
    foreach ($metrics as $metric) {
        $statLabel = $metric['label']; $statValue = number_format((int) $metric['value']); $statNote = $metric['note']; $statIcon = $metric['icon']; $statTone = $metric['tone'];
        require __DIR__ . '/views/components/stat-card.php';
    }
    ?>
</section>

<section class="panel mb-4" data-reveal>
    <header class="panel-header"><div><h2>Create account</h2><p>New Tester accounts get an automatic linked tester profile. Roles stay on the approved list.</p></div></header>
    <form class="form-grid account-create-form" method="post" action="users.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-field"><label for="create-name">Full name</label><input id="create-name" name="name" maxlength="120" required></div>
        <div class="form-field"><label for="create-username">Username</label><input id="create-username" name="username" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" required></div>
        <div class="form-field"><label for="create-email">Email (optional)</label><input id="create-email" name="email" type="email" maxlength="190"></div>
        <div class="form-field"><label for="create-role">Role</label><select id="create-role" name="role" required><option value="">Choose role</option><?php foreach ($roleOptions as $roleName => $description): ?><option value="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
        <div class="form-field"><label for="create-password">Temporary password</label><input id="create-password" name="password" type="password" minlength="12" autocomplete="new-password" required><span class="form-hint">At least 12 characters.</span></div>
        <div class="form-field account-submit"><label aria-hidden="true">&nbsp;</label><button class="button button-primary" type="submit"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add account</button></div>
    </form>
</section>

<section class="panel" data-reveal>
    <header class="panel-header"><div><h2>Staff accounts</h2><p>Change the access role or deactivate/reactivate an account. Lab records are retained.</p></div><span class="status-pill"><?php echo count($users); ?> total</span></header>
    <?php if ($users === []): ?>
        <?php $emptyTitle = 'No accounts found'; $emptyText = 'Create the first account using the form above.'; $emptyIcon = 'fa-users'; require __DIR__ . '/views/components/empty-state.php'; ?>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table account-table">
        <thead><tr><th>Account</th><th>Role</th><th>Status</th><th>Created</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><span class="table-primary"><?php echo htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="table-secondary">@<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($user['email'] ?? 'No email'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><span class="role-chip"><?php echo htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php $badgeLabel = (int) $user['is_active'] === 1 ? 'Active' : 'Inactive'; $badgeTone = $badgeLabel; require __DIR__ . '/views/components/status-badge.php'; ?></td>
                <td><?php echo htmlspecialchars(date('d M Y', strtotime((string) $user['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <details class="account-edit-details">
                        <summary class="button button-secondary"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit</summary>
                        <form class="account-edit-form" method="post" action="users.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                            <div class="form-field"><label>Name</label><input name="name" value="<?php echo htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" required></div>
                            <div class="form-field"><label>Username</label><input name="username" value="<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" required></div>
                            <div class="form-field"><label>Email</label><input name="email" type="email" value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="190"></div>
                            <div class="form-field"><label>Role</label><select name="role" required><?php foreach ($roleOptions as $roleName => $description): ?><option value="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $user['role'] === $roleName ? ' selected' : ''; ?>><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                            <div class="form-field"><label>Access</label><select name="is_active"><option value="1"<?php echo (int) $user['is_active'] === 1 ? ' selected' : ''; ?>>Active — can sign in</option><option value="0"<?php echo (int) $user['is_active'] !== 1 ? ' selected' : ''; ?>>Deactivated — preserve history</option></select></div>
                            <div class="form-field"><label>Reset password (optional)</label><input name="new_password" type="password" minlength="12" autocomplete="new-password" placeholder="Leave blank to keep existing"></div>
                            <div class="form-actions"><button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save account</button></div>
                        </form>
                    </details>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
