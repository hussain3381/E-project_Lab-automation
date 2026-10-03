<?php
include "db.php";

/* =========================
   STATUS COUNTS
========================= */

$total_tests = 0;
$pending_tests = 0;
$in_progress = 0;
$completed_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_tests = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests WHERE status = 'Pending'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $pending_tests = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests WHERE status = 'In Progress'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $in_progress = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests WHERE status = 'Completed'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $completed_tests = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests WHERE result = 'PASS'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $passed_tests = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tests WHERE result = 'FAIL'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $failed_tests = $row["total"];
}


/* =========================
   ALL TESTING RECORDS
========================= */

$tests = mysqli_query($conn, "
    SELECT
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
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Testing Status | Lab Automation</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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

body {

    font-family: "Inter", sans-serif;

    background:
        radial-gradient(
            circle at top right,
            rgba(72, 215, 196, 0.045),
            transparent 30%
        ),
        #071014;

    color: #e7f5f3;

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

    border-right: 1px solid rgba(255,255,255,0.05);

    padding: 25px 16px;

    z-index: 100;

    display: flex;

    flex-direction: column;
}


/* =========================
   BRAND
========================= */

.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 0 10px 25px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.brand-icon {

    width: 40px;
    height: 40px;

    border-radius: 10px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: rgba(72,215,196,0.08);

    color: #48d7c4;

    font-size: 20px;

    border: 1px solid rgba(72,215,196,0.12);
}

.brand-text {

    display: flex;

    flex-direction: column;
}

.brand-text strong {

    font-family: "Space Grotesk", sans-serif;

    color: #e8f8f5;

    font-size: 17px;

    letter-spacing: 0.5px;
}

.brand-text span {

    color: #62797b;

    font-size: 10px;

    margin-top: 3px;

    text-transform: uppercase;

    letter-spacing: 1.2px;
}


/* =========================
   NAVIGATION
========================= */

.nav-title {

    font-size: 10px;

    color: #52686a;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    margin: 25px 12px 10px;
}

.nav {

    display: flex;

    flex-direction: column;

    gap: 4px;
}

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    text-decoration: none;

    color: #829799;

    padding: 11px 12px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 500;

    border: 1px solid transparent;

    transition: 0.2s;

    position: relative;
}

.nav-link:hover {

    color: #d9eeeb;

    background: rgba(72,215,196,0.06);
}

.nav-link.active {

    color: #48d7c4;

    background: rgba(72,215,196,0.09);

    border-color: rgba(72,215,196,0.10);
}

.nav-link.active::before {

    content: "";

    position: absolute;

    left: -16px;

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
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 16px;

    border-top: 1px solid rgba(255,255,255,0.05);
}

.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px 8px;
}

.user-avatar {

    width: 34px;
    height: 34px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #48d7c4;

    color: #061110;

    font-weight: 700;

    font-size: 12px;
}

.user-info strong {

    display: block;

    color: #dcefed;

    font-size: 12px;
}

.user-info span {

    display: block;

    color: #5f7577;

    font-size: 10px;

    margin-top: 2px;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 245px;

    padding: 30px 35px;

    min-height: 100vh;
}


/* =========================
   TOPBAR
========================= */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;
}

.topbar h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    color: #edf8f6;

    margin-bottom: 6px;
}

.topbar p {

    color: #62797b;

    font-size: 13px;
}


/* =========================
   STATS
========================= */

.stats {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 24px;
}

.stat-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 21px 22px;

    transition: 0.2s;
}

.stat-card:hover {

    border-color: rgba(72,215,196,0.14);
}

.stat-card p {

    color: #62797b;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.7px;
}

.stat-card h3 {

    margin-top: 8px;

    font-family: "Space Grotesk", sans-serif;

    font-size: 28px;

    color: #48d7c4;
}

.stat-card.pass h3 {

    color: #48d7c4;
}

.stat-card.fail h3 {

    color: #ff8d8d;
}

.stat-card.pending h3 {

    color: #e5c86c;
}


/* =========================
   STATUS OVERVIEW
========================= */

.overview {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 24px;

    margin-bottom: 24px;
}

.overview h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #e8f5f3;

    margin-bottom: 22px;
}

.status-row {

    margin-bottom: 18px;
}

.status-row:last-child {

    margin-bottom: 0;
}

.status-info {

    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    font-size: 12px;
}

.status-info span:first-child {

    color: #8ca3a3;
}

.status-info span:last-child {

    color: #dcefed;

    font-weight: 600;
}

.progress {

    width: 100%;

    height: 8px;

    background: #071014;

    border-radius: 20px;

    overflow: hidden;
}

.progress-bar {

    height: 100%;

    border-radius: 20px;

    background: #48d7c4;

    transition: width 0.4s ease;
}

.progress-bar.pending {

    background: #e5c86c;
}

.progress-bar.progressing {

    background: #62b9e8;
}

.progress-bar.completed {

    background: #48d7c4;
}


/* =========================
   TABLE
========================= */

.table-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}

.table-header {

    padding: 21px 24px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.table-header h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #e8f5f3;
}

.table-header p {

    color: #62797b;

    font-size: 11px;

    margin-top: 5px;
}

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 950px;
}

th {

    padding: 14px 16px;

    text-align: left;

    font-size: 10px;

    color: #607779;

    text-transform: uppercase;

    letter-spacing: 0.8px;

    background: #09171b;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}

td {

    padding: 15px 16px;

    border-bottom: 1px solid rgba(255,255,255,0.045);

    font-size: 13px;

    color: #bdcfcc;
}

tr:hover td {

    background: rgba(72,215,196,0.025);
}


/* =========================
   RESULT BADGES
========================= */

.result-pass {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    color: #48d7c4;

    background: rgba(72,215,196,0.08);

    border: 1px solid rgba(72,215,196,0.10);

    font-size: 10px;

    font-weight: 600;
}

.result-fail {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    color: #ff8d8d;

    background: rgba(255,80,80,0.07);

    border: 1px solid rgba(255,80,80,0.10);

    font-size: 10px;

    font-weight: 600;
}

.result-pending {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    color: #e5c86c;

    background: rgba(229,200,108,0.07);

    border: 1px solid rgba(229,200,108,0.10);

    font-size: 10px;

    font-weight: 600;
}


/* =========================
   STATUS BADGES
========================= */

.status-badge {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 600;

    background: rgba(255,255,255,0.05);

    color: #9ebaba;
}

.status-completed {

    color: #48d7c4;

    background: rgba(72,215,196,0.08);
}

.status-progress {

    color: #62b9e8;

    background: rgba(98,185,232,0.08);
}

.status-pending {

    color: #e5c86c;

    background: rgba(229,200,108,0.08);
}


/* =========================
   VIEW BUTTON
========================= */

.view-btn {

    display: inline-block;

    padding: 7px 12px;

    border-radius: 7px;

    text-decoration: none;

    color: #48d7c4;

    background: rgba(72,215,196,0.04);

    border: 1px solid rgba(72,215,196,0.16);

    font-size: 11px;

    transition: 0.2s;
}

.view-btn:hover {

    background: rgba(72,215,196,0.09);

    border-color: rgba(72,215,196,0.25);
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 35px !important;

    color: #617779;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1100px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;

        padding: 25px;
    }

    .stats {

        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .sidebar-bottom {

        margin-top: 20px;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .stats {

        grid-template-columns: 1fr;
    }

    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-icon">
            ⚡
        </div>

        <div class="brand-text">

            <strong>LAB AUTOMATION</strong>

            <span>Electrical Testing</span>

        </div>

    </div>


    <div class="nav-title">
        Main Menu
    </div>


    <nav class="nav">


        <a href="dashboard.php" class="nav-link">

            <span class="nav-icon">⌂</span>

            <span>Dashboard</span>

        </a>


        <a href="products.php" class="nav-link">

            <span class="nav-icon">▣</span>

            <span>Products</span>

        </a>


        <a href="testing.php" class="nav-link">

            <span class="nav-icon">✓</span>

            <span>Testing</span>

        </a>


        <a href="test-types.php" class="nav-link">

            <span class="nav-icon">▤</span>

            <span>Test Types</span>

        </a>


        <a href="search.php" class="nav-link">

            <span class="nav-icon">⌕</span>

            <span>Advanced Search</span>

        </a>


        <a href="reports.php" class="nav-link">

            <span class="nav-icon">▥</span>

            <span>Reports</span>

        </a>


        <a href="testing-status.php" class="nav-link active">

            <span class="nav-icon">◉</span>

            <span>Testing Status</span>

        </a>


        <a href="testers.php" class="nav-link">

            <span class="nav-icon">♙</span>

            <span>Testers</span>

        </a>


        <a href="users.php" class="nav-link">

            <span class="nav-icon">♙</span>

            <span>Users</span>

        </a>


        <a href="settings.php" class="nav-link">

            <span class="nav-icon">⚙</span>

            <span>Settings</span>

        </a>


        <a href="logout.php" class="nav-link">

            <span class="nav-icon">↪</span>

            <span>Logout</span>

        </a>


    </nav>


    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">
                LA
            </div>

            <div class="user-info">

                <strong>Lab Administrator</strong>

                <span>Administrator</span>

            </div>

        </div>

    </div>


</aside>



<!-- =========================
     MAIN
========================= -->

<main class="main">


    <div class="topbar">

        <div>

            <h2>
                Testing Status
            </h2>

            <p>
                Monitor current laboratory testing progress
            </p>

        </div>

    </div>



    <!-- =========================
         STAT CARDS
    ========================= -->

    <div class="stats">


        <div class="stat-card">

            <p>
                Total Tests
            </p>

            <h3>
                <?php echo $total_tests; ?>
            </h3>

        </div>


        <div class="stat-card pending">

            <p>
                Pending Tests
            </p>

            <h3>
                <?php echo $pending_tests; ?>
            </h3>

        </div>


        <div class="stat-card">

            <p>
                In Progress
            </p>

            <h3>
                <?php echo $in_progress; ?>
            </h3>

        </div>


        <div class="stat-card">

            <p>
                Completed
            </p>

            <h3>
                <?php echo $completed_tests; ?>
            </h3>

        </div>


        <div class="stat-card pass">

            <p>
                Passed
            </p>

            <h3>
                <?php echo $passed_tests; ?>
            </h3>

        </div>


        <div class="stat-card fail">

            <p>
                Failed
            </p>

            <h3>
                <?php echo $failed_tests; ?>
            </h3>

        </div>


    </div>



    <!-- =========================
         STATUS OVERVIEW
    ========================= -->

    <div class="overview">

        <h3>
            Testing Progress Overview
        </h3>


        <?php

        $total_for_progress = max($total_tests, 1);

        $pending_percent =
            ($pending_tests / $total_for_progress) * 100;

        $progress_percent =
            ($in_progress / $total_for_progress) * 100;

        $completed_percent =
            ($completed_tests / $total_for_progress) * 100;

        ?>


        <div class="status-row">

            <div class="status-info">

                <span>
                    Pending
                </span>

                <span>
                    <?php echo $pending_tests; ?>
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar pending"
                    style="width: <?php echo $pending_percent; ?>%;"
                ></div>

            </div>

        </div>



        <div class="status-row">

            <div class="status-info">

                <span>
                    In Progress
                </span>

                <span>
                    <?php echo $in_progress; ?>
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar progressing"
                    style="width: <?php echo $progress_percent; ?>%;"
                ></div>

            </div>

        </div>



        <div class="status-row">

            <div class="status-info">

                <span>
                    Completed
                </span>

                <span>
                    <?php echo $completed_tests; ?>
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar completed"
                    style="width: <?php echo $completed_percent; ?>%;"
                ></div>

            </div>

        </div>


    </div>



    <!-- =========================
         TESTING TABLE
    ========================= -->

    <div class="table-card">


        <div class="table-header">

            <h3>
                Testing Status Records
            </h3>

            <p>
                Current status of all laboratory tests
            </p>

        </div>


        <div class="table-wrapper">

            <table>


                <thead>

                    <tr>

                        <th>Test ID</th>

                        <th>Product ID</th>

                        <th>Product</th>

                        <th>Test Type</th>

                        <th>Tester</th>

                        <th>Date</th>

                        <th>Result</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($tests && mysqli_num_rows($tests) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($tests)): ?>


                        <tr>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["test_id"]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["product_id"]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["product_name"] ?? "N/A"
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["test_name"] ?? "N/A"
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["tester_name"] ?? "N/A"
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo !empty($row["testing_date"])

                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $row["testing_date"]
                                        )
                                    )

                                    : "N/A";

                                ?>

                            </td>


                            <td>


                                <?php if ($row["result"] == "PASS"): ?>


                                    <span class="result-pass">
                                        PASS
                                    </span>


                                <?php elseif ($row["result"] == "FAIL"): ?>


                                    <span class="result-fail">
                                        FAIL
                                    </span>


                                <?php else: ?>


                                    <span class="result-pending">
                                        PENDING
                                    </span>


                                <?php endif; ?>


                            </td>


                            <td>


                                <?php if ($row["status"] == "Completed"): ?>


                                    <span class="status-badge status-completed">
                                        Completed
                                    </span>


                                <?php elseif ($row["status"] == "In Progress"): ?>


                                    <span class="status-badge status-progress">
                                        In Progress
                                    </span>


                                <?php else: ?>


                                    <span class="status-badge status-pending">
                                        Pending
                                    </span>


                                <?php endif; ?>


                            </td>


                            <td>

                                <a
                                    href="test-details.php?id=<?php echo $row["id"]; ?>"
                                    class="view-btn"
                                >
                                    View
                                </a>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            class="empty"
                        >
                            No testing records found.
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