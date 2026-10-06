<?php

include "db.php";

// Apply the minimum role boundary for this module.
require_roles(['Administrator']);

$message = "";
$error = "";


/* =========================
   ADD USER
========================= */

if (isset($_POST["add_user"])) {

    $name = trim($_POST["name"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    if ($name == "" || $username == "" || $password == "" || $role == "") {

        $error = "Please fill all fields.";

    } else {

        // Check duplicate username
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $username
        );

        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {

            $error = "Username already exists.";

        } else {

            // Hash password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (name, username, password, role)
                VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $username,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "User added successfully!";

            } else {

                $error = "Unable to add user.";
            }
        }
    }
}


/* =========================
   DELETE USER
========================= */

if (isset($_POST["delete_user"]) && ctype_digit((string) $_POST["delete_user"])) {

    $delete_id = (int) $_POST["delete_user"];

    // Protect main administrator
    if ($delete_id == 1) {

        $error = "Main administrator account cannot be deleted.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM users WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $delete_id
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "User deleted successfully!";

        } else {

            $error = "Unable to delete user.";
        }
    }
}


/* =========================
   USER STATISTICS
========================= */

$total_users = 0;
$admin_users = 0;
$lab_managers = 0;


/* Total Users */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_users = $row["total"];
}


/* Administrators */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'Administrator'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $admin_users = $row["total"];
}


/* Lab Managers */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'Lab Manager'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $lab_managers = $row["total"];
}


/* =========================
   GET ALL USERS
========================= */

$users = mysqli_query(
    $conn,
    "SELECT id, name, username, role, created_at
     FROM users
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Users | Lab Automation</title>


<!-- GOOGLE FONTS -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<link rel="stylesheet" href="assets/css/pages/users.css">

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        </div>

        <div>

            <div class="brand-text">
                LAB AUTOMATION
            </div>

            <div class="brand-subtitle">
                Electrical Testing
            </div>

        </div>

    </div>


    <!-- NAV TITLE -->

    <div class="nav-title">
        Main Menu
    </div>


    <!-- NAVIGATION -->

    <nav class="nav">

        <a
            href="dashboard.php"
            class="nav-link"
        >
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>


        <a
            href="products.php"
            class="nav-link"
        >
            <span class="nav-icon">▣</span>
            <span>Products</span>
        </a>


        <a
            href="testing.php"
            class="nav-link"
        >
            <span class="nav-icon"><i class="fa-solid fa-flask" aria-hidden="true"></i></span>
            <span>Testing</span>
        </a>


        <a
            href="test-types.php"
            class="nav-link"
        >
            <span class="nav-icon">◈</span>
            <span>Test Types</span>
        </a>


        <a
            href="testing-status.php"
            class="nav-link"
        >
            <span class="nav-icon">◷</span>
            <span>Testing Status</span>
        </a>


        <a
            href="search.php"
            class="nav-link"
        >
            <span class="nav-icon">⌕</span>
            <span>Advanced Search</span>
        </a>


        <a
            href="reports.php"
            class="nav-link"
        >
            <span class="nav-icon">▤</span>
            <span>Reports</span>
        </a>


        <a
            href="testers.php"
            class="nav-link"
        >
            <span class="nav-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
            <span>Testers</span>
        </a>


        <a
            href="settings.php"
            class="nav-link"
        >
            <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>
            <span>Settings</span>
        </a>


        <a
            href="users.php"
            class="nav-link active"
        >
            <span class="nav-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
            <span>Users</span>
        </a>

    </nav>


    <!-- USER -->

    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">
                LA
            </div>

            <div class="user-info">

                <strong>
                    Lab Administrator
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

    </div>

</aside>



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
                        placeholder="Enter password"
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

                        <option value="Administrator">
                            Administrator
                        </option>

                        <option value="Lab Manager">
                            Lab Manager
                        </option>

                        <option value="Tester">
                            Tester
                        </option>

                        <option value="Quality Control">
                            Quality Control
                        </option>

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


                                <?php if ($row["id"] == 1): ?>


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