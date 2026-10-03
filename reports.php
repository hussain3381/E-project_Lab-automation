<?php

include "db.php";


/* =========================
   BASIC COUNTS
========================= */

$total_products = 0;
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$pending_tests = 0;


/* Total Products */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_products = $row['total'];
}


/* Total Tests */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM tests"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_tests = $row['total'];
}


/* Passed Tests */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'PASS'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $passed_tests = $row['total'];
}


/* Failed Tests */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'FAIL'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $failed_tests = $row['total'];
}


/* Pending Tests */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'PENDING'
        OR result IS NULL
        OR result = ''"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $pending_tests = $row['total'];
}


/* =========================
   TESTING STATUS
========================= */

$pending_status = 0;
$in_progress_status = 0;
$completed_status = 0;


$result = mysqli_query(
    $conn,
    "SELECT
        SUM(status = 'Pending') AS pending,
        SUM(status = 'In Progress') AS in_progress,
        SUM(status = 'Completed') AS completed
     FROM tests"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $pending_status = $row['pending'] ?? 0;
    $in_progress_status = $row['in_progress'] ?? 0;
    $completed_status = $row['completed'] ?? 0;
}


/* =========================
   DEPARTMENT REPORT
========================= */

$department_report = mysqli_query(
    $conn,
    "SELECT
        test_types.department,
        COUNT(tests.id) AS total_tests,
        SUM(tests.result = 'PASS') AS passed,
        SUM(tests.result = 'FAIL') AS failed
     FROM test_types

     LEFT JOIN tests
        ON tests.test_type_id = test_types.id

     GROUP BY test_types.department

     ORDER BY total_tests DESC"
);


/* =========================
   TEST TYPE REPORT
========================= */

$test_type_report = mysqli_query(
    $conn,
    "SELECT
        test_types.test_name,
        test_types.test_code,
        COUNT(tests.id) AS total_tests,
        SUM(tests.result = 'PASS') AS passed,
        SUM(tests.result = 'FAIL') AS failed
     FROM test_types

     LEFT JOIN tests
        ON tests.test_type_id = test_types.id

     GROUP BY test_types.id

     ORDER BY total_tests DESC"
);


/* =========================
   RECENT TESTS
========================= */

$recent_tests = mysqli_query(
    $conn,
    "SELECT
        tests.id,
        tests.test_id,
        tests.product_id,
        tests.testing_date,
        tests.result,
        tests.status,

        products.product_name,

        test_types.test_name,

        testers.name AS tester_name

     FROM tests

     LEFT JOIN products
        ON tests.product_id = products.product_id

     LEFT JOIN test_types
        ON tests.test_type_id = test_types.id

     LEFT JOIN testers
        ON tests.tester_id = testers.id

     ORDER BY tests.id DESC

     LIMIT 10"
);


/* =========================
   PASS RATE
========================= */

$pass_rate = 0;

if ($total_tests > 0) {

    $pass_rate = round(
        ($passed_tests / $total_tests) * 100
    );
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

<title>Reports | Lab Automation</title>


<!-- GOOGLE FONTS -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================
   BODY
========================= */

body {

    font-family: "Inter", sans-serif;

    background: #071014;

    color: #ffffff;

    min-height: 100vh;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 245px;
    height: 100vh;

    background: #09171b;

    border-right: 1px solid rgba(255,255,255,0.055);

    padding: 25px 14px;

    display: flex;

    flex-direction: column;

    z-index: 1000;
}


/* =========================
   BRAND
========================= */

.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 4px 10px 25px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}


.brand-icon {

    width: 38px;
    height: 38px;

    border-radius: 11px;

    background: rgba(72,215,196,0.10);

    border: 1px solid rgba(72,215,196,0.18);

    display: flex;

    align-items: center;
    justify-content: center;

    color: #48d7c4;

    font-size: 20px;
}


.brand-text {

    font-family: "Space Grotesk", sans-serif;

    font-size: 15px;

    font-weight: 700;

    letter-spacing: 0.5px;

    color: #ffffff;
}


.brand-subtitle {

    color: #62797b;

    font-size: 9px;

    margin-top: 3px;

    letter-spacing: 0.5px;
}


/* =========================
   NAV TITLE
========================= */

.nav-title {

    color: #4e6567;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1.4px;

    padding: 24px 12px 10px;

    text-transform: uppercase;
}


/* =========================
   NAVIGATION
========================= */

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    text-decoration: none;

    color: #829799;

    padding: 11px 12px;

    margin: 3px 0;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 500;

    border: 1px solid transparent;

    transition: all 0.25s ease;

    position: relative;
}


.nav-link:hover {

    color: #48d7c4;

    background: rgba(72,215,196,0.06);

    border-color: rgba(72,215,196,0.08);
}


.nav-link.active {

    color: #48d7c4;

    background: rgba(72,215,196,0.09);

    border-color: rgba(72,215,196,0.10);
}


.nav-link.active::before {

    content: "";

    position: absolute;

    left: -14px;

    top: 7px;

    bottom: 7px;

    width: 3px;

    background: #48d7c4;

    border-radius: 0 4px 4px 0;
}


.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;

    opacity: 0.9;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom {

    margin-top: auto;

    border-top: 1px solid rgba(255,255,255,0.05);

    padding-top: 15px;
}


.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 8px 10px;
}


.user-avatar {

    width: 34px;
    height: 34px;

    border-radius: 50%;

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

    color: #d9e5e4;

    font-size: 11px;
}


.user-info span {

    display: block;

    color: #536a6c;

    font-size: 9px;

    margin-top: 3px;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 245px;

    min-height: 100vh;

    padding: 30px 35px;

    background:
        radial-gradient(
            circle at top right,
            rgba(72,215,196,0.045),
            transparent 35%
        );
}


/* =========================
   HEADER
========================= */

.header {

    margin-bottom: 28px;
}


.header small {

    color: #48d7c4;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1.4px;

    text-transform: uppercase;
}


.header h1 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    line-height: 1.2;

    margin-top: 5px;

    color: #ffffff;
}


.header p {

    color: #62797b;

    font-size: 12px;

    margin-top: 7px;
}


/* =========================
   SUMMARY
========================= */

.summary {

    display: grid;

    grid-template-columns: repeat(5, 1fr);

    gap: 15px;

    margin-bottom: 22px;
}


.summary-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 19px;

    min-height: 120px;

    transition: 0.25s ease;
}


.summary-card:hover {

    border-color: rgba(72,215,196,0.16);

    transform: translateY(-2px);
}


.summary-card .label {

    color: #62797b;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1px;

    display: block;

    margin-bottom: 10px;
}


.summary-card h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 28px;

    color: #48d7c4;
}


.summary-card.failed h2 {

    color: #ff8d82;
}


.summary-card.pending h2 {

    color: #e4d27a;
}


/* =========================
   TWO COLUMNS
========================= */

.two-column {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;

    margin-bottom: 22px;
}


/* =========================
   REPORT CARD
========================= */

.report-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}


.card-header {

    padding: 19px 21px;

    border-bottom: 1px solid rgba(255,255,255,0.055);
}


.card-header h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 16px;

    color: #e7eeee;
}


.card-header p {

    color: #62797b;

    font-size: 10px;

    margin-top: 5px;
}


/* =========================
   PASS RATE
========================= */

.pass-rate {

    padding: 25px;
}


.rate-number {

    display: flex;

    align-items: center;

    gap: 22px;
}


.rate-circle {

    width: 110px;
    height: 110px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    position: relative;

    background:
        conic-gradient(
            #48d7c4
            <?php echo $pass_rate; ?>%,
            #172b2f
            <?php echo $pass_rate; ?>%
        );
}


.rate-circle::before {

    content: "";

    position: absolute;

    width: 84px;
    height: 84px;

    border-radius: 50%;

    background: #0b1a1e;
}


.rate-value {

    position: relative;

    z-index: 2;

    font-family: "Space Grotesk", sans-serif;

    font-size: 24px;

    color: #48d7c4;
}


.rate-info h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 15px;

    margin-bottom: 7px;

    color: #ffffff;
}


.rate-info p {

    color: #62797b;

    font-size: 11px;

    line-height: 1.6;
}


/* =========================
   STATUS
========================= */

.status-area {

    padding: 22px;
}


.status-row {

    margin-bottom: 18px;
}


.status-row:last-child {

    margin-bottom: 0;
}


.status-label {

    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    font-size: 11px;
}


.status-label span:first-child {

    color: #9eb1b0;
}


.status-label span:last-child {

    color: #48d7c4;

    font-weight: 700;
}


.progress {

    height: 7px;

    background: #132528;

    border-radius: 10px;

    overflow: hidden;
}


.progress-bar {

    height: 100%;

    border-radius: 10px;

    background: #48d7c4;
}


.progress-bar.yellow {

    background: #e4d27a;
}


.progress-bar.red {

    background: #ff7777;
}


/* =========================
   TABLE CARD
========================= */

.table-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;

    margin-bottom: 22px;
}


.table-wrapper {

    overflow-x: auto;
}


/* =========================
   TABLE
========================= */

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 850px;
}


th {

    text-align: left;

    padding: 13px 17px;

    color: #718889;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    background: rgba(255,255,255,0.018);

    border-bottom: 1px solid rgba(255,255,255,0.055);
}


td {

    padding: 14px 17px;

    border-top: 1px solid rgba(255,255,255,0.035);

    color: #b6c5c4;

    font-size: 11px;
}


tr:hover td {

    background: rgba(72,215,196,0.025);
}


/* =========================
   IDs
========================= */

.id {

    color: #48d7c4;

    font-family: "Space Grotesk", sans-serif;

    font-weight: 700;
}


.product {

    color: #e1e9e8;

    font-weight: 600;
}


/* =========================
   BADGES
========================= */

.badge {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;
}


.pass {

    color: #55dfcd;

    background: rgba(72,215,196,0.09);

    border: 1px solid rgba(72,215,196,0.12);
}


.fail {

    color: #ff8d82;

    background: rgba(255,100,90,0.09);

    border: 1px solid rgba(255,100,90,0.12);
}


.pending {

    color: #e4d27a;

    background: rgba(228,210,122,0.08);

    border: 1px solid rgba(228,210,122,0.11);
}


/* =========================
   VIEW BUTTON
========================= */

.view-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    color: #48d7c4;

    background: rgba(72,215,196,0.07);

    border: 1px solid rgba(72,215,196,0.13);

    padding: 6px 11px;

    border-radius: 7px;

    font-size: 9px;

    font-weight: 600;

    transition: 0.2s ease;
}


.view-btn:hover {

    background: #48d7c4;

    color: #061110;

    border-color: #48d7c4;
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 35px !important;

    color: #62797b;

    white-space: normal !important;
}


/* =========================
   SECTION SPACING
========================= */

.section-space {

    margin-bottom: 22px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1200px) {

    .summary {

        grid-template-columns: repeat(3, 1fr);
    }

}


@media(max-width: 950px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 25px;
    }

    .two-column {

        grid-template-columns: 1fr;
    }

}


@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .brand {

        justify-content: center;
    }

    .nav-title {

        padding-top: 18px;
    }

    .sidebar-bottom {

        margin-top: 15px;
    }

    .main {

        margin-left: 0;

        padding: 20px 15px;
    }

    .summary {

        grid-template-columns: 1fr 1fr;
    }

}


@media(max-width: 500px) {

    .summary {

        grid-template-columns: 1fr;
    }

    .header h1 {

        font-size: 24px;
    }

    .rate-number {

        flex-direction: column;

        align-items: flex-start;
    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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

    <nav>


        <a
            href="dashboard.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⌂
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="products.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ▣
            </span>

            <span>
                Products
            </span>

        </a>


        <a
            href="testing.php"
            class="nav-link"
        >

            <span class="nav-icon">
                🧪
            </span>

            <span>
                Testing
            </span>

        </a>


        <a
            href="test-types.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ◈
            </span>

            <span>
                Test Types
            </span>

        </a>


        <a
            href="search.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⌕
            </span>

            <span>
                Advanced Search
            </span>

        </a>


        <a
            href="reports.php"
            class="nav-link active"
        >

            <span class="nav-icon">
                ▤
            </span>

            <span>
                Reports
            </span>

        </a>


        <a
            href="testers.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ◎
            </span>

            <span>
                Testers
            </span>

        </a>


        <a
            href="settings.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⚙
            </span>

            <span>
                Settings
            </span>

        </a>


    </nav>


    <!-- SIDEBAR BOTTOM -->

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


        <a
            href="logout.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ↪
            </span>

            <span>
                Logout
            </span>

        </a>


    </div>


</aside>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- HEADER -->

    <div class="header">

        <small>
            Laboratory Analytics
        </small>

        <h1>
            Laboratory Reports
        </h1>

        <p>
            Overview of products, testing results and laboratory performance.
        </p>

    </div>



    <!-- =========================
         SUMMARY
    ========================= -->

    <section class="summary">


        <!-- PRODUCTS -->

        <div class="summary-card">

            <span class="label">
                TOTAL PRODUCTS
            </span>

            <h2>
                <?php echo $total_products; ?>
            </h2>

        </div>


        <!-- TESTS -->

        <div class="summary-card">

            <span class="label">
                TOTAL TESTS
            </span>

            <h2>
                <?php echo $total_tests; ?>
            </h2>

        </div>


        <!-- PASSED -->

        <div class="summary-card">

            <span class="label">
                PASSED TESTS
            </span>

            <h2>
                <?php echo $passed_tests; ?>
            </h2>

        </div>


        <!-- FAILED -->

        <div class="summary-card failed">

            <span class="label">
                FAILED TESTS
            </span>

            <h2>
                <?php echo $failed_tests; ?>
            </h2>

        </div>


        <!-- PENDING -->

        <div class="summary-card pending">

            <span class="label">
                PENDING TESTS
            </span>

            <h2>
                <?php echo $pending_tests; ?>
            </h2>

        </div>


    </section>



    <!-- =========================
         TWO COLUMN
    ========================= -->

    <section class="two-column">


        <!-- PASS RATE -->

        <div class="report-card">


            <div class="card-header">

                <h2>
                    Testing Result Overview
                </h2>

                <p>
                    Overall test pass percentage
                </p>

            </div>


            <div class="pass-rate">


                <div class="rate-number">


                    <div class="rate-circle">

                        <span class="rate-value">
                            <?php echo $pass_rate; ?>%
                        </span>

                    </div>


                    <div class="rate-info">

                        <h3>
                            Overall Pass Rate
                        </h3>

                        <p>

                            <?php echo $passed_tests; ?>

                            tests passed out of

                            <?php echo $total_tests; ?>

                            total tests.

                        </p>

                    </div>


                </div>


            </div>


        </div>



        <!-- TESTING STATUS -->

        <div class="report-card">


            <div class="card-header">

                <h2>
                    Testing Status
                </h2>

                <p>
                    Current testing workflow status
                </p>

            </div>


            <div class="status-area">


                <!-- PENDING -->

                <div class="status-row">

                    <div class="status-label">

                        <span>
                            Pending
                        </span>

                        <span>
                            <?php echo $pending_status; ?>
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar yellow"
                            style="width:
                            <?php
                            echo $total_tests > 0
                                ? ($pending_status / $total_tests) * 100
                                : 0;
                            ?>%;"
                        ></div>

                    </div>

                </div>



                <!-- IN PROGRESS -->

                <div class="status-row">

                    <div class="status-label">

                        <span>
                            In Progress
                        </span>

                        <span>
                            <?php echo $in_progress_status; ?>
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="width:
                            <?php
                            echo $total_tests > 0
                                ? ($in_progress_status / $total_tests) * 100
                                : 0;
                            ?>%;"
                        ></div>

                    </div>

                </div>



                <!-- COMPLETED -->

                <div class="status-row">

                    <div class="status-label">

                        <span>
                            Completed
                        </span>

                        <span>
                            <?php echo $completed_status; ?>
                        </span>

                    </div>


                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="width:
                            <?php
                            echo $total_tests > 0
                                ? ($completed_status / $total_tests) * 100
                                : 0;
                            ?>%;"
                        ></div>

                    </div>

                </div>


            </div>


        </div>


    </section>



    <!-- =========================
         DEPARTMENT REPORT
    ========================= -->

    <section class="report-card section-space">


        <div class="card-header">

            <h2>
                Department Testing Report
            </h2>

            <p>
                Testing activity by laboratory department
            </p>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Department
                        </th>

                        <th>
                            Total Tests
                        </th>

                        <th>
                            Passed
                        </th>

                        <th>
                            Failed
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (
                    $department_report &&
                    mysqli_num_rows($department_report) > 0
                ) {

                    while (
                        $row =
                        mysqli_fetch_assoc($department_report)
                    ) {

                ?>


                    <tr>


                        <td class="product">

                            <?php

                            echo htmlspecialchars(
                                $row['department']
                            );

                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['total_tests'];
                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['passed'] ?? 0;
                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['failed'] ?? 0;
                            ?>

                        </td>


                    </tr>


                <?php

                    }

                } else {

                ?>


                    <tr>

                        <td
                            colspan="4"
                            class="empty"
                        >

                            No department testing data found.

                        </td>

                    </tr>


                <?php } ?>


                </tbody>


            </table>


        </div>


    </section>



    <!-- =========================
         TEST TYPE REPORT
    ========================= -->

    <section class="report-card section-space">


        <div class="card-header">

            <h2>
                Test Type Report
            </h2>

            <p>
                Testing results according to test type
            </p>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Test Code
                        </th>

                        <th>
                            Test Type
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Passed
                        </th>

                        <th>
                            Failed
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (
                    $test_type_report &&
                    mysqli_num_rows($test_type_report) > 0
                ) {

                    while (
                        $row =
                        mysqli_fetch_assoc($test_type_report)
                    ) {

                ?>


                    <tr>


                        <td class="id">

                            <?php

                            echo htmlspecialchars(
                                $row['test_code']
                            );

                            ?>

                        </td>


                        <td class="product">

                            <?php

                            echo htmlspecialchars(
                                $row['test_name']
                            );

                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['total_tests'];
                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['passed'] ?? 0;
                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row['failed'] ?? 0;
                            ?>

                        </td>


                    </tr>


                <?php

                    }

                } else {

                ?>


                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >

                            No test type data found.

                        </td>

                    </tr>


                <?php } ?>


                </tbody>


            </table>


        </div>


    </section>



    <!-- =========================
         RECENT TESTING
    ========================= -->

    <section class="table-card">


        <div class="card-header">

            <h2>
                Recent Testing Activity
            </h2>

            <p>
                Latest laboratory testing records
            </p>

        </div>


        <div class="table-wrapper">


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
                            Tester
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Result
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (
                    $recent_tests &&
                    mysqli_num_rows($recent_tests) > 0
                ) {

                    while (
                        $row =
                        mysqli_fetch_assoc($recent_tests)
                    ) {


                        if ($row['result'] == "PASS") {

                            $result_class = "pass";

                        }

                        elseif ($row['result'] == "FAIL") {

                            $result_class = "fail";

                        }

                        else {

                            $result_class = "pending";

                        }

                ?>


                    <tr>


                        <td class="id">

                            <?php

                            echo htmlspecialchars(
                                $row['test_id']
                            );

                            ?>

                        </td>


                        <td class="product">

                            <?php

                            echo htmlspecialchars(
                                $row['product_name']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['test_name']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['tester_name'] ?: "—"
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['testing_date'] ?: "—"
                            );

                            ?>

                        </td>


                        <td>

                            <span
                                class="badge <?php echo $result_class; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row['result']
                                    ?: "PENDING"
                                );

                                ?>

                            </span>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['status']
                            );

                            ?>

                        </td>


                        <td>

                            <a
                                href="test-details.php?id=<?php echo $row['id']; ?>"
                                class="view-btn"
                            >
                                View
                            </a>

                        </td>


                    </tr>


                <?php

                    }

                } else {

                ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >

                            No testing records found.

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