<?php

require_once __DIR__ . "/config/security.php";
app_start_session();

include "db.php";


/* =========================================================
   DASHBOARD DATABASE DATA
========================================================= */


/* ---------- TOTAL PRODUCTS ---------- */

$product_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$product_data = mysqli_fetch_assoc($product_query);

$total_products = $product_data['total'] ?? 0;


/* ---------- TOTAL TESTS ---------- */

$test_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM tests"
);

$test_data = mysqli_fetch_assoc($test_query);

$total_tests = $test_data['total'] ?? 0;


/* ---------- COMPLETED TESTS ---------- */

$completed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE status = 'Completed'"
);

$completed_data = mysqli_fetch_assoc($completed_query);

$completed_tests = $completed_data['total'] ?? 0;


/* ---------- PENDING TESTS ---------- */

$pending_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE status = 'Pending'"
);

$pending_data = mysqli_fetch_assoc($pending_query);

$pending_tests = $pending_data['total'] ?? 0;


/* ---------- FAILED TESTS ---------- */

$failed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'FAIL'"
);

$failed_data = mysqli_fetch_assoc($failed_query);

$failed_tests = $failed_data['total'] ?? 0;


/* ---------- IN PROGRESS ---------- */

$in_progress_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE status = 'In Progress'"
);

$in_progress_data = mysqli_fetch_assoc($in_progress_query);

$in_progress_tests = $in_progress_data['total'] ?? 0;


/* =========================================================
   RECENT TESTING ACTIVITY
========================================================= */

$recent_tests = mysqli_query(
    $conn,
    "SELECT
        tests.id,
        tests.test_id,
        tests.product_id,
        tests.result,
        tests.status,
        products.product_name,
        test_types.test_name
     
     FROM tests

     LEFT JOIN products
        ON tests.product_id = products.product_id

     LEFT JOIN test_types
        ON tests.test_type_id = test_types.id

     ORDER BY tests.id DESC

     LIMIT 4"
);


/* =========================================================
   TESTING STATUS
========================================================= */

$total_for_status = $total_tests > 0 ? $total_tests : 1;


/* Pending percentage */

$pending_percentage = round(
    ($pending_tests / $total_for_status) * 100
);


/* In Progress percentage */

$in_progress_percentage = round(
    ($in_progress_tests / $total_for_status) * 100
);


/* Completed percentage */

$completed_percentage = round(
    ($completed_tests / $total_for_status) * 100
);


/* Failed percentage */

$failed_percentage = round(
    ($failed_tests / $total_for_status) * 100
);


/* =========================================================
   USER INFORMATION
========================================================= */

$user_name = $_SESSION['name'] ?? 'Administrator';
$user_role = $_SESSION['role'] ?? 'Lab Manager';
$is_admin = $user_role === 'Administrator';
$can_manage_lab = in_array($user_role, ['Administrator', 'Lab Manager'], true);
$can_manage_workflow = in_array($user_role, ['Administrator', 'Lab Manager', 'Quality Control'], true);

/* User initials */

$name_parts = explode(" ", trim($user_name));

$user_initials = "";

foreach ($name_parts as $part) {

    if ($part != "") {

        $user_initials .= strtoupper(
            substr($part, 0, 1)
        );
    }

    if (strlen($user_initials) >= 2) {
        break;
    }
}

if ($user_initials == "") {
    $user_initials = "AD";
}

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

    <title>Lab Automation | Dashboard</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <link rel="stylesheet" href="assets/css/pages/dashboard.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================== -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-icon">
            ⚡
        </div>

        <div class="brand-text">

            <h2>LAB AUTOMATION</h2>

            <p>Electrical Testing</p>

        </div>

    </div>


    <div class="nav-title">
        Main Menu
    </div>


    <a href="dashboard.php"
       class="nav-link active">

        <span class="nav-icon">⌂</span>

        <span>Dashboard</span>

    </a>


    <a href="products.php"
       class="nav-link">

        <span class="nav-icon">▣</span>

        <span>Products</span>

    </a>


    <a href="testing.php"
       class="nav-link">

        <span class="nav-icon">⌁</span>

        <span>Testing</span>

    </a>


    <?php if ($can_manage_lab): ?>
    <a href="test-types.php" class="nav-link">
        <span class="nav-icon">◈</span>
        <span>Test Types</span>
    </a>
    <?php endif; ?>


    <div class="nav-title">
        Management
    </div>


    <a href="search.php"
       class="nav-link">

        <span class="nav-icon">⌕</span>

        <span>Advanced Search</span>

    </a>


    <a href="reports.php"
       class="nav-link">

        <span class="nav-icon">▥</span>

        <span>Reports</span>

    </a>


    <?php if ($can_manage_lab): ?>
    <a href="testers.php" class="nav-link">
        <span class="nav-icon">♙</span>
        <span>Testers</span>
    </a>
    <a href="departments.php" class="nav-link">
        <span class="nav-icon">▦</span>
        <span>Departments</span>
    </a>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <a href="product-catalog.php" class="nav-link">
        <span class="nav-icon">▧</span>
        <span>Product Catalog</span>
    </a>
    <a href="product-test-plan.php" class="nav-link">
        <span class="nav-icon">☷</span>
        <span>Family Test Plan</span>
    </a>
    <?php endif; ?>

    <?php if ($can_manage_workflow): ?>
    <a href="product-workflow.php" class="nav-link">
        <span class="nav-icon">↻</span>
        <span>Product Workflow</span>
    </a>
    <?php endif; ?>

    <div class="nav-title">
        System
    </div>

    <?php if ($is_admin): ?>
    <a href="users.php" class="nav-link">
        <span class="nav-icon">♟</span>
        <span>Users</span>
    </a>
    <a href="roles.php" class="nav-link">
        <span class="nav-icon">♜</span>
        <span>Roles &amp; Access</span>
    </a>
    <?php endif; ?>

    <?php if ($can_manage_lab): ?>
    <a href="settings.php" class="nav-link">
        <span class="nav-icon">⚙</span>
        <span>Settings</span>
    </a>
    <?php endif; ?>


    <a href="logout.php"
       class="nav-link">

        <span class="nav-icon">↪</span>

        <span>Logout</span>

    </a>


    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">

                <?php
                echo htmlspecialchars($user_initials);
                ?>

            </div>


            <div class="user-info">

                <strong>
                    <?php
                    echo htmlspecialchars($user_name);
                    ?>
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars($user_role);
                    ?>
                </span>

            </div>

        </div>

    </div>


</aside>


<!-- =========================
     MAIN CONTENT
========================== -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">


        <div class="welcome">

            <small>
                Laboratory Control Center
            </small>

            <h1>
                Dashboard Overview
            </h1>

            <p>
                Monitor products, testing activity and laboratory results.
            </p>

        </div>


        <div class="date-box">

            <?php
            echo date("d F Y");
            ?>

        </div>


    </div>


    <!-- =========================
         STAT CARDS
    ========================== -->

    <section class="stats">


        <!-- TOTAL PRODUCTS -->

        <div class="stat-card">

            <div class="stat-top">

                <span class="stat-title">
                    Total Products
                </span>

                <div class="stat-icon">
                    ▣
                </div>

            </div>


            <div class="stat-number">

                <?php
                echo number_format($total_products);
                ?>

            </div>


            <div class="stat-change">

                <span>Live</span>
                database records

            </div>

        </div>


        <!-- COMPLETED TESTS -->

        <div class="stat-card">

            <div class="stat-top">

                <span class="stat-title">
                    Tests Completed
                </span>

                <div class="stat-icon">
                    ✓
                </div>

            </div>


            <div class="stat-number">

                <?php
                echo number_format($completed_tests);
                ?>

            </div>


            <div class="stat-change">

                <span>Live</span>
                testing records

            </div>

        </div>


        <!-- PENDING TESTS -->

        <div class="stat-card">

            <div class="stat-top">

                <span class="stat-title">
                    Pending Tests
                </span>

                <div class="stat-icon">
                    ◷
                </div>

            </div>


            <div class="stat-number">

                <?php
                echo number_format($pending_tests);
                ?>

            </div>


            <div class="stat-change">

                <span>Live</span>
                requires attention

            </div>

        </div>


        <!-- FAILED TESTS -->

        <div class="stat-card">

            <div class="stat-top">

                <span class="stat-title">
                    Failed Tests
                </span>

                <div class="stat-icon">
                    !
                </div>

            </div>


            <div class="stat-number">

                <?php
                echo number_format($failed_tests);
                ?>

            </div>


            <div class="stat-change">

                <span>Live</span>
                failed testing records

            </div>

        </div>


    </section>

    <?php if ($is_admin): ?>
    <section class="admin-access-panel" aria-labelledby="admin-access-title">
        <div>
            <p class="admin-access-eyebrow">ADMINISTRATION</p>
            <h2 id="admin-access-title">Users &amp; roles</h2>
            <p>Create staff logins, assign one of the four approved roles, and review access boundaries.</p>
        </div>
        <div class="admin-access-actions">
            <a href="users.php">Manage users <span aria-hidden="true">→</span></a>
            <a href="roles.php">View roles <span aria-hidden="true">→</span></a>
        </div>
    </section>
    <?php endif; ?>

    <!-- =========================
         CONTENT
    ========================== -->

    <section class="content-grid">


        <!-- RECENT TESTING -->

        <div class="panel">


            <div class="panel-header">

                <h3>
                    Recent Testing Activity
                </h3>


                <a
                    href="testing.php"
                    class="view-all"
                >
                    View all →
                </a>

            </div>


            <table>

                <thead>

                    <tr>

                        <th>
                            Test ID
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Test Type
                        </th>

                        <th>
                            Result
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php

                    if (
                        $recent_tests &&
                        mysqli_num_rows($recent_tests) > 0
                    ):

                        while (
                            $row =
                            mysqli_fetch_assoc($recent_tests)
                        ):

                    ?>

                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['test_id']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['product_id']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['test_name']
                                    ?? 'N/A'
                                );
                                ?>

                            </td>


                            <td>


                                <?php

                                $result =
                                    strtoupper(
                                        $row['result'] ?? ''
                                    );


                                if ($result == "PASS") {

                                    echo '<span class="badge pass">
                                            PASS
                                          </span>';

                                }

                                elseif ($result == "FAIL") {

                                    echo '<span class="badge fail">
                                            FAIL
                                          </span>';

                                }

                                else {

                                    echo '<span class="badge pending">
                                            PENDING
                                          </span>';
                                }

                                ?>

                            </td>

                        </tr>


                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td
                                colspan="4"
                                style="
                                    text-align:center;
                                    color:var(--legacy-color-536a6c);
                                    padding:30px;
                                "
                            >

                                No testing records found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>


        </div>



        <!-- TEST STATUS -->

        <div class="panel">


            <div class="panel-header">

                <h3>
                    Testing Status
                </h3>

                <span class="view-all">
                    Live
                </span>

            </div>


            <div class="status-list">


                <!-- PENDING -->

                <div class="status-item">

                    <div class="status-row">

                        <span>
                            Pending Testing
                        </span>

                        <span>
                            <?php
                            echo $pending_percentage;
                            ?>%
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="
                                width:
                                <?php
                                echo min(
                                    $pending_percentage,
                                    100
                                );
                                ?>%;
                            "
                        ></div>

                    </div>

                </div>


                <!-- IN PROGRESS -->

                <div class="status-item">

                    <div class="status-row">

                        <span>
                            In Progress
                        </span>

                        <span>
                            <?php
                            echo $in_progress_percentage;
                            ?>%
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="
                                width:
                                <?php
                                echo min(
                                    $in_progress_percentage,
                                    100
                                );
                                ?>%;
                            "
                        ></div>

                    </div>

                </div>


                <!-- COMPLETED -->

                <div class="status-item">

                    <div class="status-row">

                        <span>
                            Completed
                        </span>

                        <span>
                            <?php
                            echo $completed_percentage;
                            ?>%
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="
                                width:
                                <?php
                                echo min(
                                    $completed_percentage,
                                    100
                                );
                                ?>%;
                            "
                        ></div>

                    </div>

                </div>


                <!-- FAILED -->

                <div class="status-item">

                    <div class="status-row">

                        <span>
                            Failed Tests
                        </span>

                        <span>
                            <?php
                            echo $failed_percentage;
                            ?>%
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="
                                width:
                                <?php
                                echo min(
                                    $failed_percentage,
                                    100
                                );
                                ?>%;
                            "
                        ></div>

                    </div>

                </div>


            </div>

        </div>


    </section>



    <!-- =========================
         QUICK ACTIONS
    ========================== -->

    <section class="quick-actions">


        <a
            href="add-product.php"
            class="action"
        >

            <div class="action-icon">
                +
            </div>


            <strong>
                Add New Product
            </strong>


            <span>
                Register a manufactured product
            </span>

        </a>



        <a
            href="new-test.php"
            class="action"
        >

            <div class="action-icon">
                ⚡
            </div>


            <strong>
                Start New Test
            </strong>


            <span>
                Create a new testing record
            </span>

        </a>



        <a
            href="search.php"
            class="action"
        >

            <div class="action-icon">
                ⌕
            </div>


            <strong>
                Search Records
            </strong>


            <span>
                Find products and testing history
            </span>

        </a>


    </section>


</main>


</body>

</html>