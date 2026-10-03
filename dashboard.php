<?php

session_start();

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


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: "Inter", sans-serif;

            background: #071014;

            color: #eefafa;

            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================== */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            width: 245px;

            height: 100vh;

            background: #09171b;

            border-right:
                1px solid rgba(72, 215, 196, 0.10);

            padding: 25px 16px;

            display: flex;

            flex-direction: column;

            z-index: 10;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 8px 10px 30px;
        }


        .brand-icon {

            width: 43px;

            height: 43px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background:
                rgba(72, 215, 196, 0.08);

            border:
                1px solid rgba(72, 215, 196, 0.25);

            font-size: 20px;
        }


        .brand h2 {

            font-family: "Space Grotesk", sans-serif;

            font-size: 15px;
        }


        .brand p {

            color: #5e7779;

            font-size: 9px;

            letter-spacing: 1.2px;

            margin-top: 3px;
        }


        /* Navigation */

        .nav-title {

            color: #486164;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            padding: 0 12px;

            margin: 10px 0 10px;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 12px;

            height: 45px;

            padding: 0 13px;

            margin-bottom: 5px;

            color: #829799;

            text-decoration: none;

            border-radius: 10px;

            font-size: 12px;

            transition: 0.25s ease;
        }


        .nav-icon {

            width: 20px;

            text-align: center;

            font-size: 14px;
        }


        .nav-link:hover {

            color: #dffefa;

            background:
                rgba(72, 215, 196, 0.06);
        }


        .nav-link.active {

            color: #48d7c4;

            background:
                rgba(72, 215, 196, 0.09);

            border:
                1px solid rgba(72, 215, 196, 0.10);
        }


        /* Bottom user */

        .sidebar-bottom {

            margin-top: auto;

            padding: 14px 10px;

            border-top:
                1px solid rgba(255,255,255,0.05);
        }


        .user-box {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .user-avatar {

            width: 35px;

            height: 35px;

            border-radius: 10px;

            background: #48d7c4;

            color: #061110;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

            font-size: 12px;
        }


        .user-info strong {

            display: block;

            font-size: 11px;
        }


        .user-info span {

            color: #536b6d;

            font-size: 9px;
        }


        /* =========================
           MAIN
        ========================== */

        .main {

            margin-left: 245px;

            min-height: 100vh;

            padding: 30px 35px;

            background:
                radial-gradient(
                    circle at 80% 10%,
                    rgba(72, 215, 196, 0.045),
                    transparent 30%
                );
        }


        /* Header */

        .topbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 35px;
        }


        .welcome small {

            color: #48d7c4;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.5px;

            text-transform: uppercase;
        }


        .welcome h1 {

            font-family: "Space Grotesk", sans-serif;

            font-size: 29px;

            margin-top: 6px;
        }


        .welcome p {

            color: #62797b;

            font-size: 12px;

            margin-top: 6px;
        }


        .date-box {

            padding: 11px 15px;

            border:
                1px solid rgba(255,255,255,0.06);

            border-radius: 10px;

            background:
                rgba(255,255,255,0.025);

            color: #7f9698;

            font-size: 11px;
        }


        /* =========================
           STAT CARDS
        ========================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }


        .stat-card {

            position: relative;

            overflow: hidden;

            min-height: 145px;

            padding: 21px;

            background: #0b1a1e;

            border:
                1px solid rgba(255,255,255,0.055);

            border-radius: 15px;

            transition: 0.25s ease;
        }


        .stat-card:hover {

            transform: translateY(-3px);

            border-color:
                rgba(72,215,196,0.18);
        }


        .stat-card::after {

            content: "";

            position: absolute;

            width: 100px;

            height: 100px;

            right: -45px;

            bottom: -50px;

            border-radius: 50%;

            background:
                rgba(72,215,196,0.06);
        }


        .stat-top {

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .stat-title {

            color: #688082;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .stat-icon {

            width: 32px;

            height: 32px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background:
                rgba(72,215,196,0.07);

            color: #48d7c4;
        }


        .stat-number {

            font-family: "Space Grotesk", sans-serif;

            font-size: 31px;

            margin-top: 18px;
        }


        .stat-change {

            margin-top: 7px;

            font-size: 9px;

            color: #587173;
        }


        .stat-change span {

            color: #48d7c4;
        }


        /* =========================
           CONTENT GRID
        ========================== */

        .content-grid {

            display: grid;

            grid-template-columns: 1.45fr 0.8fr;

            gap: 20px;
        }


        .panel {

            background: #0b1a1e;

            border:
                1px solid rgba(255,255,255,0.055);

            border-radius: 15px;

            overflow: hidden;
        }


        .panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 20px 21px;

            border-bottom:
                1px solid rgba(255,255,255,0.05);
        }


        .panel-header h3 {

            font-family: "Space Grotesk", sans-serif;

            font-size: 14px;
        }


        .view-all {

            color: #48d7c4;

            text-decoration: none;

            font-size: 10px;
        }


        /* =========================
           TABLE
        ========================== */

        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            color: #4f6769;

            font-size: 9px;

            font-weight: 600;

            text-align: left;

            padding: 13px 20px;

            text-transform: uppercase;

            letter-spacing: 0.7px;
        }


        td {

            padding: 14px 20px;

            border-top:
                1px solid rgba(255,255,255,0.035);

            color: #91a5a6;

            font-size: 10px;
        }


        td:first-child {

            color: #d3e1e1;

            font-weight: 600;
        }


        .badge {

            display: inline-block;

            padding: 5px 8px;

            border-radius: 6px;

            font-size: 8px;

            font-weight: 700;
        }


        .pass {

            color: #48d7c4;

            background:
                rgba(72,215,196,0.08);
        }


        .pending {

            color: #d8b66a;

            background:
                rgba(216,182,106,0.08);
        }


        .fail {

            color: #df7b7b;

            background:
                rgba(223,123,123,0.08);
        }


        /* =========================
           RIGHT PANEL
        ========================== */

        .status-list {

            padding: 5px 20px 20px;
        }


        .status-item {

            padding: 16px 0;

            border-bottom:
                1px solid rgba(255,255,255,0.04);
        }


        .status-item:last-child {

            border-bottom: none;
        }


        .status-row {

            display: flex;

            justify-content: space-between;

            margin-bottom: 9px;
        }


        .status-row span:first-child {

            color: #819799;

            font-size: 10px;
        }


        .status-row span:last-child {

            color: #c3d0d0;

            font-size: 10px;

            font-weight: 600;
        }


        .progress {

            height: 5px;

            background: #14272b;

            border-radius: 10px;

            overflow: hidden;
        }


        .progress-bar {

            height: 100%;

            border-radius: 10px;

            background: #48d7c4;
        }


        /* =========================
           QUICK ACTIONS
        ========================== */

        .quick-actions {

            margin-top: 20px;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;
        }


        .action {

            text-decoration: none;

            padding: 18px;

            background: #0b1a1e;

            border:
                1px solid rgba(255,255,255,0.05);

            border-radius: 13px;

            color: #8ca2a3;

            transition: 0.25s ease;
        }


        .action:hover {

            border-color:
                rgba(72,215,196,0.20);

            transform: translateY(-2px);
        }


        .action-icon {

            width: 35px;

            height: 35px;

            border-radius: 9px;

            background:
                rgba(72,215,196,0.07);

            color: #48d7c4;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 12px;
        }


        .action strong {

            display: block;

            color: #d5e2e2;

            font-size: 11px;

            margin-bottom: 5px;
        }


        .action span {

            font-size: 9px;

            color: #536a6c;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 1100px) {

            .stats {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .content-grid {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 750px) {

            .sidebar {

                width: 70px;

                padding: 20px 8px;
            }


            .brand {

                justify-content: center;

                padding-bottom: 25px;
            }


            .brand-text,
            .nav-title,
            .nav-link span:not(.nav-icon),
            .user-info {

                display: none;
            }


            .nav-link {

                justify-content: center;

                padding: 0;
            }


            .main {

                margin-left: 70px;

                padding: 25px 18px;
            }


            .quick-actions {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 500px) {

            .stats {

                grid-template-columns: 1fr;
            }


            .topbar {

                align-items: flex-start;
            }


            .date-box {

                display: none;
            }
        }

    </style>

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


    <a href="test-types.php"
       class="nav-link">

        <span class="nav-icon">◈</span>

        <span>Test Types</span>

    </a>


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


    <a href="testers.php"
       class="nav-link">

        <span class="nav-icon">♙</span>

        <span>Testers</span>

    </a>


    <div class="nav-title">
        System
    </div>


    <a href="settings.php"
       class="nav-link">

        <span class="nav-icon">⚙</span>

        <span>Settings</span>

    </a>


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
                                    color:#536a6c;
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