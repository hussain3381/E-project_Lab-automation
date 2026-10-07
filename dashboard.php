<?php

require_once __DIR__ . "/config/security.php";
app_start_session();

include "db.php";

function dashboard_run_query(mysqli $connection, string $sql): mysqli_result
{
    try {
        $result = mysqli_query($connection, $sql);
    } catch (mysqli_sql_exception $exception) {
        error_log('Dashboard query failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Dashboard data could not be loaded. Please try again later.');
    }

    if ($result === false) {
        error_log('Dashboard query failed: ' . mysqli_error($connection));
        http_response_code(500);
        exit('Dashboard data could not be loaded. Please try again later.');
    }

    return $result;
}

/* =========================================================
   DASHBOARD DATABASE DATA
========================================================= */

/* One aggregate query keeps all dashboard counts consistent. */
$summary_result = dashboard_run_query(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM products) AS total_products,
        COUNT(*) AS total_tests,
        COALESCE(SUM(UPPER(status) = 'COMPLETED'), 0) AS completed_tests,
        COALESCE(SUM(UPPER(status) = 'PENDING'), 0) AS pending_tests,
        COALESCE(SUM(UPPER(status) = 'IN PROGRESS'), 0) AS in_progress_tests,
        COALESCE(SUM(UPPER(result) = 'FAIL'), 0) AS failed_tests
     FROM tests"
);
$summary = mysqli_fetch_assoc($summary_result);

if ($summary === false) {
    error_log('Dashboard summary query returned no row.');
    http_response_code(500);
    exit('Dashboard data could not be loaded. Please try again later.');
}

$total_products = (int) $summary['total_products'];
$total_tests = (int) $summary['total_tests'];
$completed_tests = (int) $summary['completed_tests'];
$pending_tests = (int) $summary['pending_tests'];
$in_progress_tests = (int) $summary['in_progress_tests'];
$failed_tests = (int) $summary['failed_tests'];


/* =========================================================
   RECENT TESTING ACTIVITY
========================================================= */

$recent_tests = dashboard_run_query(
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


/* Failed is a test result, not a workflow status, so it is not part of this breakdown. */
$pending_percentage = $total_tests > 0
    ? (int) round(($pending_tests / $total_tests) * 100)
    : 0;
$in_progress_percentage = $total_tests > 0
    ? (int) round(($in_progress_tests / $total_tests) * 100)
    : 0;
$completed_percentage = $total_tests > 0
    ? (int) round(($completed_tests / $total_tests) * 100)
    : 0;


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
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
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
       class="nav-link active"
       aria-label="Dashboard"
       title="Dashboard">

        <span class="nav-icon"><i class="fa-solid fa-house" aria-hidden="true"></i></span>

        <span>Dashboard</span>

    </a>


    <a href="products.php"
       class="nav-link"
       aria-label="Products"
       title="Products">

        <span class="nav-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span>

        <span>Products</span>

    </a>


    <a href="testing.php"
       class="nav-link"
       aria-label="Testing"
       title="Testing">

        <span class="nav-icon"><i class="fa-solid fa-flask" aria-hidden="true"></i></span>

        <span>Testing</span>

    </a>


    <a href="test-types.php"
       class="nav-link"
       aria-label="Test Types"
       title="Test Types">

        <span class="nav-icon"><i class="fa-solid fa-vials" aria-hidden="true"></i></span>

        <span>Test Types</span>

    </a>


    <div class="nav-title">
        Management
    </div>


    <a href="search.php"
       class="nav-link"
       aria-label="Advanced Search"
       title="Advanced Search">

        <span class="nav-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>

        <span>Advanced Search</span>

    </a>


    <a href="reports.php"
       class="nav-link"
       aria-label="Reports"
       title="Reports">

        <span class="nav-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>

        <span>Reports</span>

    </a>


    <a href="testers.php"
       class="nav-link"
       aria-label="Testers"
       title="Testers">

        <span class="nav-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>

        <span>Testers</span>

    </a>


    <div class="nav-title">
        System
    </div>


    <a href="settings.php"
       class="nav-link"
       aria-label="Settings"
       title="Settings">

        <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>

        <span>Settings</span>

    </a>


    <a href="logout.php"
       class="nav-link"
       aria-label="Logout"
       title="Logout">

        <span class="nav-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span>

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
                    <i class="fa-solid fa-box" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
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
                    View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>

            </div>

            <div class="table-scroll">
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
                                    (string) ($row['product_name'] ?: $row['product_id']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string) ($row['test_name'] ?? 'N/A'),
                                    ENT_QUOTES,
                                    'UTF-8'
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
                            <?php echo number_format($pending_tests); ?> · <?php echo $pending_percentage; ?>%
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
                            <?php echo number_format($in_progress_tests); ?> · <?php echo $in_progress_percentage; ?>%
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
                            <?php echo number_format($completed_tests); ?> · <?php echo $completed_percentage; ?>%
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
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
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
                <i class="fa-solid fa-vial" aria-hidden="true"></i>
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
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
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