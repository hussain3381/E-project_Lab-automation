<?php

include "db.php";

$results = null;

$product_id = "";
$product_name = "";
$product_code = "";
$test_id = "";
$test_type = "";
$tester = "";
$result_filter = "";
$status = "";
$testing_date = "";


/* =========================
   SEARCH
========================= */

if (isset($_GET['search'])) {

    $product_id    = trim($_GET['product_id'] ?? "");
    $product_name  = trim($_GET['product_name'] ?? "");
    $product_code  = trim($_GET['product_code'] ?? "");
    $test_id       = trim($_GET['test_id'] ?? "");
    $test_type     = trim($_GET['test_type'] ?? "");
    $tester        = trim($_GET['tester'] ?? "");
    $result_filter = trim($_GET['result'] ?? "");
    $status        = trim($_GET['status'] ?? "");
    $testing_date  = trim($_GET['testing_date'] ?? "");


    $sql = "
        SELECT
            tests.id,
            tests.test_id,
            tests.product_id,
            tests.testing_date,
            tests.result,
            tests.status,
            tests.remarks,

            products.product_name,
            products.product_code,
            products.product_type,
            products.revision,

            test_types.test_name,
            test_types.test_code,

            testers.name AS tester_name

        FROM tests

        LEFT JOIN products
            ON tests.product_id = products.product_id

        LEFT JOIN test_types
            ON tests.test_type_id = test_types.id

        LEFT JOIN testers
            ON tests.tester_id = testers.id

        WHERE 1=1
    ";

    $params = [];
    $types = "";


    /* Product ID */
    if ($product_id != "") {
        $sql .= " AND tests.product_id LIKE ?";
        $params[] = "%" . $product_id . "%";
        $types .= "s";
    }


    /* Product Name */
    if ($product_name != "") {
        $sql .= " AND products.product_name LIKE ?";
        $params[] = "%" . $product_name . "%";
        $types .= "s";
    }


    /* Product Code */
    if ($product_code != "") {
        $sql .= " AND products.product_code LIKE ?";
        $params[] = "%" . $product_code . "%";
        $types .= "s";
    }


    /* Test ID */
    if ($test_id != "") {
        $sql .= " AND tests.test_id LIKE ?";
        $params[] = "%" . $test_id . "%";
        $types .= "s";
    }


    /* Test Type */
    if ($test_type != "") {
        $sql .= " AND tests.test_type_id = ?";
        $params[] = $test_type;
        $types .= "i";
    }


    /* Tester */
    if ($tester != "") {
        $sql .= " AND tests.tester_id = ?";
        $params[] = $tester;
        $types .= "i";
    }


    /* Result */
    if ($result_filter != "") {
        $sql .= " AND tests.result = ?";
        $params[] = $result_filter;
        $types .= "s";
    }


    /* Status */
    if ($status != "") {
        $sql .= " AND tests.status = ?";
        $params[] = $status;
        $types .= "s";
    }


    /* Testing Date */
    if ($testing_date != "") {
        $sql .= " AND tests.testing_date = ?";
        $params[] = $testing_date;
        $types .= "s";
    }


    $sql .= " ORDER BY tests.id DESC";


    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        if (!empty($params)) {
            mysqli_stmt_bind_param(
                $stmt,
                $types,
                ...$params
            );
        }

        mysqli_stmt_execute($stmt);

        $results = mysqli_stmt_get_result($stmt);
    }
}


/* =========================
   TEST TYPES
========================= */

$test_types = mysqli_query(
    $conn,
    "SELECT id, test_code, test_name
     FROM test_types
     ORDER BY test_name ASC"
);


/* =========================
   TESTERS
========================= */

$testers = mysqli_query(
    $conn,
    "SELECT id, name
     FROM testers
     ORDER BY name ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Advanced Search | Lab Automation</title>


<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
      rel="stylesheet">


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

    background:
        radial-gradient(
            circle at top right,
            rgba(72, 215, 196, 0.045),
            transparent 30%
        ),
        #071014;

    color: #e7f8f5;

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

    border-right:
        1px solid rgba(255,255,255,0.05);

    padding: 24px 16px;

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

    gap: 11px;

    padding: 0 9px 24px;

    border-bottom:
        1px solid rgba(255,255,255,0.05);

    margin-bottom: 22px;
}


.brand-icon {

    width: 38px;
    height: 38px;

    border-radius: 10px;

    background:
        rgba(72,215,196,0.10);

    border:
        1px solid rgba(72,215,196,0.16);

    display: flex;

    align-items: center;
    justify-content: center;

    color: #48d7c4;

    font-size: 19px;
}


.brand-text {

    font-family: "Space Grotesk", sans-serif;

    font-size: 16px;

    font-weight: 700;

    color: #eefcf9;

    letter-spacing: 0.5px;
}


.brand-subtitle {

    color: #607678;

    font-size: 9px;

    margin-top: 3px;

    text-transform: uppercase;

    letter-spacing: 1.2px;
}


/* =========================
   NAV TITLE
========================= */

.nav-title {

    color: #506769;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    padding: 0 10px;

    margin-bottom: 9px;
}


/* =========================
   NAVIGATION
========================= */

.nav {

    display: flex;

    flex-direction: column;

    gap: 4px;
}


.nav-link {

    display: flex;

    align-items: center;

    gap: 11px;

    text-decoration: none;

    color: #829799;

    padding: 10px 11px;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 500;

    border:
        1px solid transparent;

    transition: 0.2s;

    position: relative;
}


.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;

    opacity: 0.9;
}


.nav-link:hover {

    color: #d9efeb;

    background:
        rgba(72,215,196,0.06);
}


.nav-link.active {

    color: #48d7c4;

    background:
        rgba(72,215,196,0.09);

    border:
        1px solid rgba(72,215,196,0.10);
}


.nav-link.active::before {

    content: "";

    position: absolute;

    left: -1px;

    top: 8px;

    bottom: 8px;

    width: 2px;

    border-radius: 2px;

    background: #48d7c4;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 16px;

    border-top:
        1px solid rgba(255,255,255,0.05);
}


.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 9px 8px;
}


.user-avatar {

    width: 32px;
    height: 32px;

    border-radius: 50%;

    background: #48d7c4;

    color: #061110;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 12px;

    font-weight: 800;
}


.user-info strong {

    display: block;

    color: #dcefed;

    font-size: 11px;
}


.user-info span {

    display: block;

    color: #5f7779;

    font-size: 9px;

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
   HEADER
========================= */

.header {

    margin-bottom: 25px;
}


.header h1 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    font-weight: 700;

    color: #edf9f7;

    margin-bottom: 6px;
}


.header p {

    color: #62797b;

    font-size: 12px;
}


/* =========================
   SEARCH CARD
========================= */

.search-card {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 24px;

    margin-bottom: 22px;
}


.search-title {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #eaf8f5;

    margin-bottom: 21px;
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 17px;
}


.field {

    display: flex;

    flex-direction: column;

    gap: 7px;
}


.field label {

    color: #849b9d;

    font-size: 11px;

    font-weight: 600;
}


.field input,
.field select {

    width: 100%;

    padding: 11px 12px;

    background: #071014;

    color: #e4f4f1;

    border:
        1px solid rgba(255,255,255,0.08);

    border-radius: 8px;

    outline: none;

    font-family: "Inter", sans-serif;

    font-size: 12px;
}


.field input::placeholder {

    color: #4f6668;
}


.field input:focus,
.field select:focus {

    border-color: #48d7c4;

    box-shadow:
        0 0 0 3px
        rgba(72,215,196,0.07);
}


/* SELECT OPTION */

.field select option {

    background: #0b1a1e;

    color: #e4f4f1;
}


/* =========================
   BUTTONS
========================= */

.buttons {

    display: flex;

    gap: 10px;

    margin-top: 21px;
}


.search-btn {

    background: #48d7c4;

    color: #061110;

    border: none;

    padding: 11px 20px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;
}


.search-btn:hover {

    background: #65e3d3;
}


.reset-btn {

    background: transparent;

    color: #829799;

    border:
        1px solid rgba(255,255,255,0.09);

    padding: 10px 20px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 12px;

    transition: 0.2s;
}


.reset-btn:hover {

    color: #48d7c4;

    border-color:
        rgba(72,215,196,0.35);
}


/* =========================
   RESULTS
========================= */

.results-card {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}


.results-header {

    padding: 20px 23px;

    border-bottom:
        1px solid rgba(255,255,255,0.05);
}


.results-header h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #eaf8f5;
}


/* =========================
   TABLE
========================= */

.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
}


th {

    text-align: left;

    padding: 14px 17px;

    color: #637a7c;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    background: #09171b;

    white-space: nowrap;
}


td {

    padding: 14px 17px;

    border-top:
        1px solid rgba(255,255,255,0.045);

    color: #b9ccca;

    font-size: 12px;

    white-space: nowrap;
}


tr:hover td {

    background:
        rgba(72,215,196,0.025);
}


.id-text {

    color: #48d7c4;

    font-weight: 600;
}


.product-name {

    color: #edf9f7;

    font-weight: 600;
}


/* =========================
   BADGES
========================= */

.badge {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;
}


.pass {

    color: #5ee8ce;

    background:
        rgba(72,215,196,0.10);
}


.fail {

    color: #ff8b8b;

    background:
        rgba(255,80,80,0.10);
}


.pending {

    color: #f3ca73;

    background:
        rgba(243,202,115,0.10);
}


/* =========================
   VIEW BUTTON
========================= */

.view-btn {

    text-decoration: none;

    color: #48d7c4;

    border:
        1px solid rgba(72,215,196,0.22);

    padding: 6px 10px;

    border-radius: 7px;

    font-size: 11px;

    transition: 0.2s;
}


.view-btn:hover {

    background:
        rgba(72,215,196,0.08);
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 40px !important;

    color: #617779;

    font-size: 12px;
}


/* =========================
   SCROLLBAR
========================= */

::-webkit-scrollbar {

    width: 7px;
    height: 7px;
}


::-webkit-scrollbar-track {

    background: #071014;
}


::-webkit-scrollbar-thumb {

    background: #1b3b3d;

    border-radius: 10px;
}


::-webkit-scrollbar-thumb:hover {

    background: #2c5b5c;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .form-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 850px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 25px;
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

        padding: 20px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .sidebar-bottom {

        margin-top: 20px;
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

        <div>

            <div class="brand-text">
                LAB AUTOMATION
            </div>

            <div class="brand-subtitle">
                Electrical Testing
            </div>

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

            <span class="nav-icon">⚗</span>

            <span>Testing</span>

        </a>


        <a href="test-types.php" class="nav-link">

            <span class="nav-icon">◈</span>

            <span>Test Types</span>

        </a>


        <a href="testing-status.php" class="nav-link">

            <span class="nav-icon">◷</span>

            <span>Testing Status</span>

        </a>


        <a href="search.php" class="nav-link active">

            <span class="nav-icon">⌕</span>

            <span>Advanced Search</span>

        </a>


        <a href="reports.php" class="nav-link">

            <span class="nav-icon">▤</span>

            <span>Reports</span>

        </a>


        <a href="testers.php" class="nav-link">

            <span class="nav-icon">♙</span>

            <span>Testers</span>

        </a>


        <a href="settings.php" class="nav-link">

            <span class="nav-icon">⚙</span>

            <span>Settings</span>

        </a>

    </nav>


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


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <div class="header">

        <h1>
            Advanced Search
        </h1>

        <p>
            Search products and laboratory testing records.
        </p>

    </div>


    <!-- =========================
         SEARCH FORM
    ========================= -->

    <section class="search-card">

        <h2 class="search-title">
            Search Testing Records
        </h2>


        <form method="GET">


            <div class="form-grid">


                <!-- Product ID -->

                <div class="field">

                    <label>
                        Product ID
                    </label>

                    <input
                        type="text"
                        name="product_id"
                        placeholder="Enter Product ID"
                        value="<?php echo htmlspecialchars($product_id); ?>"
                    >

                </div>


                <!-- Product Name -->

                <div class="field">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        placeholder="Enter Product Name"
                        value="<?php echo htmlspecialchars($product_name); ?>"
                    >

                </div>


                <!-- Product Code -->

                <div class="field">

                    <label>
                        Product Code
                    </label>

                    <input
                        type="text"
                        name="product_code"
                        placeholder="Enter Product Code"
                        value="<?php echo htmlspecialchars($product_code); ?>"
                    >

                </div>


                <!-- Test ID -->

                <div class="field">

                    <label>
                        Test ID
                    </label>

                    <input
                        type="text"
                        name="test_id"
                        placeholder="Enter Test ID"
                        value="<?php echo htmlspecialchars($test_id); ?>"
                    >

                </div>


                <!-- Test Type -->

                <div class="field">

                    <label>
                        Test Type
                    </label>

                    <select name="test_type">

                        <option value="">
                            All Test Types
                        </option>

                        <?php

                        if ($test_types) {

                            while ($type = mysqli_fetch_assoc($test_types)) {

                        ?>

                            <option
                                value="<?php echo $type['id']; ?>"
                                <?php
                                if ($test_type == $type['id']) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $type['test_code']
                                    . " - "
                                    . $type['test_name']
                                );
                                ?>

                            </option>

                        <?php

                            }

                        }

                        ?>

                    </select>

                </div>


                <!-- Tester -->

                <div class="field">

                    <label>
                        Tester
                    </label>

                    <select name="tester">

                        <option value="">
                            All Testers
                        </option>

                        <?php

                        if ($testers) {

                            while ($tester_row = mysqli_fetch_assoc($testers)) {

                        ?>

                            <option
                                value="<?php echo $tester_row['id']; ?>"
                                <?php
                                if ($tester == $tester_row['id']) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $tester_row['name']
                                );
                                ?>

                            </option>

                        <?php

                            }

                        }

                        ?>

                    </select>

                </div>


                <!-- Result -->

                <div class="field">

                    <label>
                        Result
                    </label>

                    <select name="result">

                        <option value="">
                            All Results
                        </option>

                        <option
                            value="PASS"
                            <?php
                            if ($result_filter == "PASS") {
                                echo "selected";
                            }
                            ?>
                        >
                            PASS
                        </option>

                        <option
                            value="FAIL"
                            <?php
                            if ($result_filter == "FAIL") {
                                echo "selected";
                            }
                            ?>
                        >
                            FAIL
                        </option>

                        <option
                            value="PENDING"
                            <?php
                            if ($result_filter == "PENDING") {
                                echo "selected";
                            }
                            ?>
                        >
                            PENDING
                        </option>

                    </select>

                </div>


                <!-- Status -->

                <div class="field">

                    <label>
                        Testing Status
                    </label>

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Pending"
                            <?php
                            if ($status == "Pending") {
                                echo "selected";
                            }
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="In Progress"
                            <?php
                            if ($status == "In Progress") {
                                echo "selected";
                            }
                            ?>
                        >
                            In Progress
                        </option>

                        <option
                            value="Completed"
                            <?php
                            if ($status == "Completed") {
                                echo "selected";
                            }
                            ?>
                        >
                            Completed
                        </option>

                    </select>

                </div>


                <!-- Date -->

                <div class="field">

                    <label>
                        Testing Date
                    </label>

                    <input
                        type="date"
                        name="testing_date"
                        value="<?php echo htmlspecialchars($testing_date); ?>"
                    >

                </div>


            </div>


            <div class="buttons">

                <button
                    type="submit"
                    name="search"
                    class="search-btn"
                >
                    Search Records
                </button>


                <a
                    href="search.php"
                    class="reset-btn"
                >
                    Reset
                </a>

            </div>


        </form>

    </section>


    <!-- =========================
         RESULTS
    ========================= -->

    <?php if ($results !== null) { ?>


    <section class="results-card">


        <div class="results-header">

            <h2>
                Search Results
            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>Test ID</th>

                        <th>Product ID</th>

                        <th>Product</th>

                        <th>Product Code</th>

                        <th>Test Type</th>

                        <th>Tester</th>

                        <th>Date</th>

                        <th>Result</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (mysqli_num_rows($results) > 0) {

                    while ($row = mysqli_fetch_assoc($results)) {

                        $result_class = "";

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

                        <td class="id-text">

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


                        <td class="product-name">

                            <?php
                            echo htmlspecialchars(
                                $row['product_name']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['product_code']
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

                            <span class="badge <?php echo $result_class; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['result'] ?: "PENDING"
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

                }
                else {

                ?>

                    <tr>

                        <td
                            colspan="10"
                            class="empty"
                        >

                            No matching testing records found.

                        </td>

                    </tr>

                <?php } ?>


                </tbody>

            </table>

        </div>

    </section>


    <?php } ?>


</main>


</body>

</html>