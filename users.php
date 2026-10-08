<?php

declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);
$message = '';
$error = '';

// Read only the registered system roles so an account cannot be assigned an arbitrary role string.
$role_options = [];
$role_result = $conn->query('SELECT role_name, description FROM roles ORDER BY id');
while ($role_row = $role_result->fetch_assoc()) {
    $role_options[(string) $role_row['role_name']] = (string) $role_row['description'];
}
$current_user_name = (string) ($_SESSION['name'] ?? 'Lab Administrator');
$current_user_role = (string) ($_SESSION['role'] ?? 'Administrator');
$current_user_initials = '';
foreach (preg_split('/\s+/', trim($current_user_name)) ?: [] as $namePart) {
    if ($namePart !== '') {
        $current_user_initials .= strtoupper(substr($namePart, 0, 1));
    }
    if (strlen($current_user_initials) >= 2) {
        break;
    }
}
$current_user_initials = $current_user_initials !== '' ? $current_user_initials : 'LA';

/* =========================
   ADD USER
========================= */
if (isset($_POST['add_user'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? ''));

    if ($name === '' || strlen($name) > 120 || $username === '' || $password === '' || $role === '') {
        $error = 'Please complete all fields. Name is required and must be 120 characters or fewer.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
        $error = 'Username must be 3–40 characters and use letters, numbers, dots, underscores, or hyphens.';
    } elseif (strlen($password) < 12) {
        $error = 'Use a password with at least 12 characters.';
    } elseif (!array_key_exists($role, $role_options)) {
        $error = 'Please select one of the registered system roles.';
    } else {
        $transactionStarted = false;
        try {
            $check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->bind_param('s', $username);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $error = 'Username already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if (!is_string($hashedPassword)) {
                    throw new RuntimeException('Could not hash the new user password.');
                }

                // A Tester account and its assigned-staff profile must be created atomically.
                $conn->begin_transaction();
                $transactionStarted = true;
                $statement = $conn->prepare(
                    'INSERT INTO users (name, username, password, role, is_active) VALUES (?, ?, ?, ?, 1)'
                );
                $statement->bind_param('ssss', $name, $username, $hashedPassword, $role);
                $statement->execute();
                $newUserId = (int) $conn->insert_id;
                $statement->close();

                if ($role === 'Tester') {
                    $profile = $conn->prepare(
                        "INSERT INTO testers (user_id, name, department, designation, is_active) VALUES (?, ?, NULL, 'Lab Tester', 1)"
                    );
                    $profile->bind_param('is', $newUserId, $name);
                    $profile->execute();
                    $profile->close();
                }

                $conn->commit();
                $transactionStarted = false;
                $message = $role === 'Tester'
                    ? 'Tester account and linked tester profile created successfully.'
                    : 'User added successfully.';
            }
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original error. */ }
            }
            error_log('User creation failed: ' . $exception->getMessage());
            $error = 'Unable to add the user. Check that the username is unique and try again.';
        }
    }
}

/* =========================
   DELETE USER
========================= */
if (isset($_POST['delete_user']) && ctype_digit((string) $_POST['delete_user'])) {
    $deleteId = (int) $_POST['delete_user'];
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

    if ($deleteId === $currentUserId) {
        $error = 'You cannot delete the account you are currently using.';
    } else {
        $lookup = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $lookup->bind_param('i', $deleteId);
        $lookup->execute();
        $target = $lookup->get_result()->fetch_assoc();
        $lookup->close();

        if ($target === null) {
            $error = 'The selected account was not found.';
        } elseif ((string) $target['role'] === 'Administrator') {
            $countResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'Administrator' AND is_active = 1");
            $activeAdministrators = (int) ($countResult->fetch_assoc()['total'] ?? 0);
            if ($activeAdministrators <= 1) {
                $error = 'The last active Administrator account cannot be deleted.';
            }
        }

        if ($error === '') {
            try {
                $statement = $conn->prepare('DELETE FROM users WHERE id = ?');
                $statement->bind_param('i', $deleteId);
                $statement->execute();
                $message = $statement->affected_rows === 1 ? 'User deleted successfully.' : 'The selected account was not found.';
                $statement->close();
            } catch (Throwable $exception) {
                error_log('User deletion failed: ' . $exception->getMessage());
                $error = 'Unable to delete the selected user.';
            }
        }
    }
}

/* =========================
   USER STATISTICS
========================= */
$statistics = $conn->query(
    "SELECT COUNT(*) AS total_users,
            SUM(role = 'Administrator' AND is_active = 1) AS administrators,
            SUM(role = 'Lab Manager' AND is_active = 1) AS lab_managers
     FROM users"
)->fetch_assoc() ?: [];
$total_users = (int) ($statistics['total_users'] ?? 0);
$admin_users = (int) ($statistics['administrators'] ?? 0);
$lab_managers = (int) ($statistics['lab_managers'] ?? 0);

/* =========================
   GET ALL USERS
========================= */
$users = $conn->query(
    'SELECT id, name, username, role, is_active, created_at FROM users ORDER BY id DESC'
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Users | Lab Automation</title>


<!-- GOOGLE FONTS -->
<link rel="stylesheet" href="assets/css/pages/users.css">

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">

        <div>

            <h2>
                User Management
            </h2>

            <p>
                Manage laboratory system users and access roles
            </p>

        </div>

    </div>



    <!-- SUCCESS MESSAGE -->

    <?php if ($message != ""): ?>

        <div class="message">

            ✓
            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>



    <!-- ERROR MESSAGE -->

    <?php if ($error != ""): ?>

        <div class="error">

            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats">


        <div class="stat-card">

            <p>
                Total Users
            </p>

            <h3>
                <?php echo $total_users; ?>
            </h3>

        </div>


        <div class="stat-card">

            <p>
                Administrators
            </p>

            <h3>
                <?php echo $admin_users; ?>
            </h3>

        </div>


        <div class="stat-card">

            <p>
                Lab Managers
            </p>

            <h3>
                <?php echo $lab_managers; ?>
            </h3>

        </div>


    </div>



    <!-- =================================================
         ADD USER FORM
    ================================================= -->

    <div class="form-card">


        <h3>
            Add New User
        </h3>


        <p>
            Create a new account for laboratory system access.
        </p>


        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">


            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        maxlength="120"
                        placeholder="Enter full name"
                        required
                    >

                </div>


                <!-- USERNAME -->

                <div class="form-group">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        minlength="3"
                        maxlength="40"
                        pattern="[A-Za-z0-9._-]+"
                        placeholder="Enter username"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        minlength="12"
                        autocomplete="new-password"
                        placeholder="At least 12 characters"
                        required
                    >

                </div>


                <!-- ROLE -->

                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <select
                        name="role"
                        required
                    >

                        <option value="">
                            Select Role
                        </option>

                        <?php foreach ($role_options as $roleName => $roleDescription): ?>
                            <option value="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"
                                title="<?php echo htmlspecialchars($roleDescription, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                </div>


            </div>


            <div class="form-buttons">

                <button
                    type="submit"
                    name="add_user"
                    class="save-btn"
                >
                    + Add User
                </button>

            </div>


        </form>

    </div>



    <!-- =================================================
         USERS TABLE
    ================================================= -->

    <div class="table-card">


        <div class="table-header">

            <h3>
                System Users
            </h3>

            <p>
                All registered users of the Lab Automation System.
            </p>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>#</th>

                        <th>Name</th>

                        <th>Username</th>

                        <th>Role</th>

                        <th>Created</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (mysqli_num_rows($users) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($users)): ?>


                        <tr>


                            <td>
                                <?php echo $row["id"]; ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row["name"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row["username"]
                                );
                                ?>
                            </td>


                            <td>

                                <span class="role">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["role"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $row["created_at"]
                                    )
                                );
                                ?>

                            </td>


                            <td>


                                <?php if ((int) $row['id'] === (int) ($_SESSION['user_id'] ?? 0) || ((string) $row['role'] === 'Administrator' && $admin_users <= 1)): ?>


                                    <span class="protected">
                                        Protected
                                    </span>


                                <?php else: ?>


                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <!-- The shared CSRF check also protects destructive actions. -->
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="delete_user" value="<?php echo (int) $row["id"]; ?>">
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            style="text-align:center; padding:30px;"
                        >
                            No users found.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>


</body>

</html>