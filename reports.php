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
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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


<link rel="stylesheet" href="assets/css/pages/reports.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
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
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
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
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
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


                    <div class="rate-circle" style="--pass-rate: <?php echo (int) $pass_rate; ?>%;">

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