<?php
include "db.php";

/* =========================
   TESTING RECORDS
========================= */

$result = mysqli_query(
    $conn,
    "SELECT 
        tests.*,
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
   NAVIGATION TITLE
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
   NAV LINKS
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

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 28px;
}


.header-left small {

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
   NEW TEST BUTTON
========================= */

.new-test-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    background: #48d7c4;

    color: #061110;

    text-decoration: none;

    padding: 11px 16px;

    border-radius: 9px;

    font-size: 11px;

    font-weight: 700;

    border: 1px solid #48d7c4;

    transition: 0.25s ease;

    white-space: nowrap;
}


.new-test-btn:hover {

    background: #62e2d1;

    transform: translateY(-2px);

    box-shadow: 0 8px 25px rgba(72,215,196,0.15);
}


/* =========================
   STATS
========================= */

.stats {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 22px;
}


.stat-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 19px;

    min-height: 130px;

    transition: 0.25s ease;
}


.stat-card:hover {

    border-color: rgba(72,215,196,0.16);

    transform: translateY(-2px);
}


.stat-label {

    color: #62797b;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1px;

    margin-bottom: 10px;
}


.stat-number {

    color: #ffffff;

    font-family: "Space Grotesk", sans-serif;

    font-size: 28px;

    font-weight: 700;
}


.stat-small {

    color: #48d7c4;

    font-size: 9px;

    margin-top: 8px;
}


/* =========================
   TABLE BOX
========================= */

.table-box {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}


/* =========================
   TABLE HEADER
========================= */

.table-header {

    padding: 20px 21px;

    border-bottom: 1px solid rgba(255,255,255,0.055);

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.table-header h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 16px;

    color: #e7eeee;
}


.table-header p {

    color: #62797b;

    font-size: 10px;

    margin-top: 4px;
}


/* =========================
   TABLE WRAPPER
========================= */

.table-scroll {

    overflow-x: auto;
}


/* =========================
   TABLE
========================= */

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;
}


th {

    background: rgba(255,255,255,0.018);

    color: #718889;

    padding: 13px 15px;

    text-align: left;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 0.7px;

    text-transform: uppercase;

    border-bottom: 1px solid rgba(255,255,255,0.055);
}


td {

    padding: 14px 15px;

    border-bottom: 1px solid rgba(255,255,255,0.035);

    color: #b6c5c4;

    font-size: 11px;

    white-space: nowrap;
}


tbody tr {

    transition: 0.2s ease;
}


tbody tr:hover {

    background: rgba(72,215,196,0.025);
}


tbody tr:last-child td {

    border-bottom: none;
}


/* =========================
   TEST ID
========================= */

.test-id {

    color: #48d7c4;

    font-weight: 700;

    font-family: "Space Grotesk", sans-serif;

    letter-spacing: 0.3px;
}


/* =========================
   PRODUCT ID
========================= */

.product-id {

    color: #9db1b0;

    font-family: "Space Grotesk", sans-serif;

    font-size: 10px;
}


/* =========================
   RESULT BADGES
========================= */

.result-pass {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    background: rgba(72,215,196,0.10);

    border: 1px solid rgba(72,215,196,0.13);

    color: #55dfcd;

    font-size: 9px;

    font-weight: 700;
}


.result-fail {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    background: rgba(255,100,90,0.09);

    border: 1px solid rgba(255,100,90,0.13);

    color: #ff8d82;

    font-size: 9px;

    font-weight: 700;
}


.result-pending {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    background: rgba(228,210,122,0.08);

    border: 1px solid rgba(228,210,122,0.12);

    color: #e4d27a;

    font-size: 9px;

    font-weight: 700;
}


/* =========================
   STATUS
========================= */

.status {

    color: #829799;

    font-size: 10px;
}


/* =========================
   VIEW BUTTON
========================= */

.view-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    background: rgba(72,215,196,0.07);

    color: #48d7c4;

    padding: 6px 11px;

    border-radius: 7px;

    border: 1px solid rgba(72,215,196,0.13);

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

    padding: 55px 20px !important;

    color: #62797b;

    white-space: normal !important;
}


.empty-icon {

    font-size: 32px;

    margin-bottom: 12px;

    opacity: 0.8;
}


.empty strong {

    color: #48d7c4;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1150px) {

    .stats {

        grid-template-columns: repeat(2, 1fr);

    }

}


@media(max-width: 850px) {

    .sidebar {

        width: 210px;

    }

    .main {

        margin-left: 210px;

        padding: 25px 20px;

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

    .header {

        flex-direction: column;

        align-items: flex-start;

    }

    .new-test-btn {

        width: 100%;

        justify-content: center;

    }

}


@media(max-width: 500px) {

    .stats {

        grid-template-columns: 1fr;

    }

    .header h1 {

        font-size: 24px;

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


                            <!-- TESTER -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['tester_name']
                                    ?? 'Not Assigned'
                                );

                                ?>

                            </td>


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
                            colspan="9"
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