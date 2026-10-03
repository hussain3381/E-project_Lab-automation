<?php

include "db.php";

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

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {

    $delete_id = intval($_GET["delete"]);

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


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    font-family: "Inter", sans-serif;

    background:
        radial-gradient(
            circle at top left,
            rgba(72, 215, 196, 0.045),
            transparent 35%
        ),
        #071014;

    color: #e7ffff;

    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 245px;
    height: 100vh;

    background: #09171b;

    border-right: 1px solid rgba(255,255,255,0.05);

    padding: 25px 18px;

    display: flex;
    flex-direction: column;

    z-index: 100;
}


/* =====================================================
   BRAND
===================================================== */

.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 0 8px 25px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}


.brand-icon {

    width: 38px;
    height: 38px;

    border-radius: 10px;

    background: rgba(72,215,196,0.09);

    color: #48d7c4;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;

    border: 1px solid rgba(72,215,196,0.12);
}


.brand-text {

    font-family: "Space Grotesk", sans-serif;

    font-size: 17px;

    font-weight: 700;

    letter-spacing: 0.7px;

    color: #e8ffff;
}


.brand-subtitle {

    margin-top: 3px;

    font-size: 10px;

    color: #607779;

    letter-spacing: 0.7px;
}


/* =====================================================
   NAVIGATION TITLE
===================================================== */

.nav-title {

    margin: 25px 10px 12px;

    font-size: 10px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    color: #52686a;
}


/* =====================================================
   NAVIGATION
===================================================== */

.nav {

    display: flex;

    flex-direction: column;

    gap: 5px;
}


.nav-link {

    position: relative;

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    color: #829799;

    text-decoration: none;

    border-radius: 8px;

    font-size: 13px;

    transition: 0.25s ease;

    border: 1px solid transparent;
}


.nav-link:hover {

    background: rgba(72,215,196,0.06);

    color: #b9d8d7;
}


.nav-link.active {

    background: rgba(72,215,196,0.09);

    border-color: rgba(72,215,196,0.10);

    color: #48d7c4;
}


.nav-link.active::before {

    content: "";

    position: absolute;

    left: -1px;

    top: 8px;

    bottom: 8px;

    width: 3px;

    border-radius: 0 4px 4px 0;

    background: #48d7c4;
}


.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;

    color: inherit;
}


/* =====================================================
   SIDEBAR BOTTOM
===================================================== */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 18px;

    border-top: 1px solid rgba(255,255,255,0.05);
}


.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px;

    border-radius: 9px;

    background: rgba(255,255,255,0.015);
}


.user-avatar {

    width: 34px;
    height: 34px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #48d7c4;

    color: #061110;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 11px;

    font-weight: 800;
}


.user-info {

    min-width: 0;
}


.user-info strong {

    display: block;

    color: #d8eeee;

    font-size: 11px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.user-info span {

    display: block;

    margin-top: 3px;

    color: #607779;

    font-size: 10px;
}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 245px;

    padding: 30px 35px;

    min-height: 100vh;
}


/* =====================================================
   HEADER
===================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    margin-bottom: 28px;
}


.topbar h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    font-weight: 600;

    color: #e8ffff;
}


.topbar p {

    margin-top: 6px;

    color: #62797b;

    font-size: 13px;
}


/* =====================================================
   MESSAGES
===================================================== */

.message {

    padding: 13px 16px;

    margin-bottom: 20px;

    background: rgba(72,215,196,0.07);

    border: 1px solid rgba(72,215,196,0.20);

    color: #48d7c4;

    border-radius: 10px;

    font-size: 13px;
}


.error {

    padding: 13px 16px;

    margin-bottom: 20px;

    background: rgba(255,80,80,0.07);

    border: 1px solid rgba(255,80,80,0.20);

    color: #ff9090;

    border-radius: 10px;

    font-size: 13px;
}


/* =====================================================
   STATS
===================================================== */

.stats {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}


.stat-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 21px;
}


.stat-card p {

    color: #62797b;

    font-size: 12px;
}


.stat-card h3 {

    margin-top: 8px;

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    color: #48d7c4;
}


/* =====================================================
   FORM CARD
===================================================== */

.form-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 24px;

    margin-bottom: 25px;
}


.form-card h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #e5ffff;

    margin-bottom: 5px;
}


.form-card > p {

    color: #62797b;

    font-size: 12px;

    margin-bottom: 22px;
}


/* =====================================================
   FORM GRID
===================================================== */

.form-grid {

    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


label {

    font-size: 12px;

    color: #91aaaa;

    margin-bottom: 8px;
}


input,
select {

    width: 100%;

    padding: 12px 13px;

    background: #071014;

    border: 1px solid rgba(255,255,255,0.08);

    border-radius: 8px;

    color: #e8ffff;

    outline: none;

    font-family: "Inter", sans-serif;

    font-size: 13px;

    transition: 0.2s;
}


input::placeholder {

    color: #4e6466;
}


input:focus,
select:focus {

    border-color: rgba(72,215,196,0.55);

    box-shadow: 0 0 0 3px rgba(72,215,196,0.06);
}


select option {

    background: #0b1a1e;

    color: #e8ffff;
}


/* =====================================================
   BUTTON
===================================================== */

.form-buttons {

    margin-top: 20px;
}


.save-btn {

    background: #48d7c4;

    color: #061110;

    border: none;

    padding: 11px 18px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;
}


.save-btn:hover {

    background: #65e3d1;

    transform: translateY(-1px);
}


/* =====================================================
   TABLE CARD
===================================================== */

.table-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}


.table-header {

    padding: 20px 22px;

    border-bottom: 1px solid rgba(255,255,255,0.055);
}


.table-header h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 17px;

    color: #e5ffff;
}


.table-header p {

    color: #62797b;

    font-size: 12px;

    margin-top: 5px;
}


.table-wrapper {

    width: 100%;

    overflow-x: auto;
}


table {

    width: 100%;

    min-width: 700px;

    border-collapse: collapse;
}


th {

    padding: 14px 16px;

    text-align: left;

    font-size: 10px;

    color: #637b7d;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    border-bottom: 1px solid rgba(255,255,255,0.05);

    white-space: nowrap;
}


td {

    padding: 15px 16px;

    border-bottom: 1px solid rgba(255,255,255,0.035);

    font-size: 13px;

    color: #c7dddd;
}


tbody tr {

    transition: 0.2s;
}


tbody tr:hover {

    background: rgba(72,215,196,0.025);
}


tbody tr:last-child td {

    border-bottom: none;
}


/* =====================================================
   ROLE BADGE
===================================================== */

.role {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    background: rgba(72,215,196,0.08);

    border: 1px solid rgba(72,215,196,0.10);

    color: #48d7c4;

    font-size: 10px;

    font-weight: 600;
}


/* =====================================================
   DELETE BUTTON
===================================================== */

.delete-btn {

    display: inline-block;

    padding: 7px 11px;

    border-radius: 7px;

    text-decoration: none;

    color: #ff9090;

    border: 1px solid rgba(255,90,90,0.25);

    background: rgba(255,90,90,0.03);

    font-size: 11px;

    transition: 0.2s;
}


.delete-btn:hover {

    background: rgba(255,90,90,0.09);

    border-color: rgba(255,90,90,0.4);
}


.protected {

    color: #607779;

    font-size: 11px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .sidebar {

        width: 225px;
    }

    .main {

        margin-left: 225px;

        padding: 28px;
    }

    .stats {

        grid-template-columns: repeat(3, 1fr);
    }
}


@media (max-width: 850px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 24px;
    }

    .stats {

        grid-template-columns: 1fr;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .main {

        margin-left: 0;

        padding: 22px 18px;
    }

    .topbar {

        flex-direction: column;

        gap: 8px;
    }

    .nav {

        gap: 3px;
    }

    .sidebar-bottom {

        margin-top: 20px;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            ⚡
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
            <span class="nav-icon">⚗</span>
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
            <span class="nav-icon">♙</span>
            <span>Testers</span>
        </a>


        <a
            href="settings.php"
            class="nav-link"
        >
            <span class="nav-icon">⚙</span>
            <span>Settings</span>
        </a>


        <a
            href="users.php"
            class="nav-link active"
        >
            <span class="nav-icon">♟</span>
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

            ⚠
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


                                    <a
                                        href="users.php?delete=<?php echo $row["id"]; ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this user?');"
                                    >
                                        Delete
                                    </a>


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