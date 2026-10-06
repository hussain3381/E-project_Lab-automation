<?php
include "db.php";

/* =========================
   TESTING RECORDS
========================= */

// Older imported databases may not have the department routing column yet.
// The migration/import SQL adds it; this fallback keeps the legacy screen usable meanwhile.
$departmentColumnCheck = mysqli_query(
    $conn,
    "SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tests'
       AND COLUMN_NAME = 'department_id'
     LIMIT 1"
);
$hasDepartmentRouting = $departmentColumnCheck && mysqli_num_rows($departmentColumnCheck) > 0;
$departmentSelect = $hasDepartmentRouting
    ? 'COALESCE(routed_department.department_name, test_types.department) AS routed_department_name'
    : 'test_types.department AS routed_department_name';
$departmentJoin = $hasDepartmentRouting
    ? 'LEFT JOIN departments AS routed_department ON tests.department_id = routed_department.id'
    : '';

$result = mysqli_query(
    $conn,
    "SELECT
        tests.*,
        products.product_name,
        test_types.test_name,
        {$departmentSelect},
        COALESCE((
            SELECT GROUP_CONCAT(DISTINCT participant.name ORDER BY participant.name SEPARATOR ', ')
            FROM test_participants AS participation
            INNER JOIN testers AS participant ON participant.id = participation.tester_id
            WHERE participation.test_record_id = tests.id
        ), testers.name, 'Not Assigned') AS tester_names
     FROM tests
     LEFT JOIN products ON tests.product_id = products.product_id
     LEFT JOIN test_types ON tests.test_type_id = test_types.id
     {$departmentJoin}
     LEFT JOIN testers ON tests.tester_id = testers.id
     ORDER BY tests.id DESC"
);


/* =========================
   STATISTICS
========================= */

$total_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM tests"
);

$total_row = mysqli_fetch_assoc($total_query);
$total_tests = $total_row['total'];


$pending_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE status = 'Pending'"
);

$pending_row = mysqli_fetch_assoc($pending_query);
$pending_tests = $pending_row['total'];


$passed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'PASS'"
);

$passed_row = mysqli_fetch_assoc($passed_query);
$passed_tests = $passed_row['total'];


$failed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tests
     WHERE result = 'FAIL'"
);

$failed_row = mysqli_fetch_assoc($failed_query);
$failed_tests = $failed_row['total'];

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

<title>Testing | Lab Automation</title>


<!-- GOOGLE FONTS -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<link rel="stylesheet" href="assets/css/pages/testing.css">

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


    <!-- NAVIGATION -->

    <div class="nav-title">
        Main Menu
    </div>


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
            class="nav-link active"
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
            class="nav-link"
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


        <div class="header-left">

            <small>
                Laboratory Testing
            </small>

            <h1>
                Testing Management
            </h1>

            <p>
                Manage laboratory testing records and results.
            </p>

        </div>


        <a
            href="new-test.php"
            class="new-test-btn"
        >

            <span>
                +
            </span>

            Start New Test

        </a>


    </div>



    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


        <!-- TOTAL -->

        <div class="stat-card">

            <div class="stat-label">
                TOTAL TESTS
            </div>

            <div class="stat-number">
                <?php echo $total_tests; ?>
            </div>

            <div class="stat-small">
                Laboratory Records
            </div>

        </div>


        <!-- PENDING -->

        <div class="stat-card">

            <div class="stat-label">
                PENDING
            </div>

            <div class="stat-number">
                <?php echo $pending_tests; ?>
            </div>

            <div class="stat-small">
                Tests Awaiting Result
            </div>

        </div>


        <!-- PASSED -->

        <div class="stat-card">

            <div class="stat-label">
                PASSED
            </div>

            <div class="stat-number">
                <?php echo $passed_tests; ?>
            </div>

            <div class="stat-small">
                Successful Tests
            </div>

        </div>


        <!-- FAILED -->

        <div class="stat-card">

            <div class="stat-label">
                FAILED
            </div>

            <div class="stat-number">
                <?php echo $failed_tests; ?>
            </div>

            <div class="stat-small">
                Tests Requiring Action
            </div>

        </div>


    </div>



    <!-- =========================
         TESTING TABLE
    ========================= -->

    <div class="table-box">


        <div class="table-header">

            <div>

                <h2>
                    Testing Records
                </h2>

                <p>
                    Complete laboratory testing history.
                </p>

            </div>

        </div>


        <div class="table-scroll">


            <table>


                <thead>

                    <tr>

                        <th>
                            Test ID
                        </th>

                        <th>
                            Product ID
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Test Type
                        </th>

                        <th>
                            Department
                        </th>

                        <th>
                            Cycle
                        </th>

                        <th>
                            Tester(s)
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


                <?php if ($result && mysqli_num_rows($result) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- TEST ID -->

                            <td>

                                <span class="test-id">

                                    <?php

                                    echo htmlspecialchars(
                                        $row['test_id']
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- PRODUCT ID -->

                            <td>

                                <span class="product-id">

                                    <?php

                                    echo htmlspecialchars(
                                        $row['product_id']
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- PRODUCT -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['product_name']
                                );

                                ?>

                            </td>


                            <!-- TEST TYPE -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['test_name']
                                );

                                ?>

                            </td>


                            <!-- ROUTED DEPARTMENT -->
                            <td><?php echo htmlspecialchars((string) ($row['routed_department_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- TEST CYCLE -->
                            <td><?php echo (int) ($row['cycle_number'] ?? 1); ?></td>

                            <!-- TESTER(S) -->
                            <td><?php echo htmlspecialchars((string) ($row['tester_names'] ?? 'Not Assigned'), ENT_QUOTES, 'UTF-8'); ?></td>


                            <!-- DATE -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['testing_date']
                                    ?? '-'
                                );

                                ?>

                            </td>


                            <!-- RESULT -->

                            <td>

                                <?php

                                $result_value =
                                    strtoupper(
                                        trim(
                                            $row['result']
                                            ?? ''
                                        )
                                    );


                                if ($result_value == 'PASS') {

                                    echo
                                    '<span class="result-pass">
                                        PASS
                                    </span>';

                                }

                                elseif ($result_value == 'FAIL') {

                                    echo
                                    '<span class="result-fail">
                                        FAIL
                                    </span>';

                                }

                                else {

                                    echo
                                    '<span class="result-pending">
                                        PENDING
                                    </span>';

                                }

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="status">

                                    <?php

                                    echo htmlspecialchars(
                                        $row['status']
                                        ?? 'Pending'
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="test-details.php?id=<?php echo $row['id']; ?>"
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
                            colspan="11"
                            class="empty"
                        >

                            <div class="empty-icon">
                                🧪
                            </div>

                            No testing records found.

                            <br><br>

                            Start your first laboratory test using
                            <strong>
                                Start New Test
                            </strong>.

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