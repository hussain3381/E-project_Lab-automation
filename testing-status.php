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
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Testing Status | Lab Automation</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
rel="stylesheet"
>


<link rel="stylesheet" href="assets/css/pages/testing-status.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
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

            <span class="nav-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>

            <span>Testers</span>

        </a>


        <a href="users.php" class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>

            <span>Users</span>

        </a>


        <a href="settings.php" class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>

            <span>Settings</span>

        </a>


        <a href="logout.php" class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span>

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