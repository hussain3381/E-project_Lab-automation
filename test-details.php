<?php

include "db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid test request.");
}

$id = intval($_GET['id']);

$sql = "SELECT 
            tests.*,
            products.product_id AS product_code_id,
            products.product_name,
            products.product_code,
            products.product_type,
            products.revision,
            test_types.test_code,
            test_types.test_name,
            test_types.department,
            testers.name AS tester_name,
            testers.designation AS tester_designation
        FROM tests
        LEFT JOIN products 
            ON tests.product_id = products.product_id
        LEFT JOIN test_types 
            ON tests.test_type_id = test_types.id
        LEFT JOIN testers 
            ON tests.tester_id = testers.id
        WHERE tests.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($result);

if (!$test) {
    die("Test record not found.");
}

function statusClass($status) {
    $status = strtolower($status);

    if ($status == "passed" || $status == "pass" || $status == "completed") {
        return "success";
    }

    if ($status == "failed" || $status == "fail") {
        return "danger";
    }

    if ($status == "pending") {
        return "warning";
    }

    return "info";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Test Details | Lab Automation</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #071417;
    color: #e8f5f3;
}

/* SIDEBAR */

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #0b1c20;
    border-right: 1px solid #16373b;
    padding: 25px 18px;
}

.logo {
    padding: 10px 12px 28px;
    border-bottom: 1px solid #16373b;
}

.logo h2 {
    font-family: 'Space Grotesk', sans-serif;
    color: #42e6c4;
    font-size: 22px;
    letter-spacing: 1px;
}

.logo p {
    color: #7e9b9d;
    font-size: 11px;
    margin-top: 5px;
}

.menu {
    margin-top: 25px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #8ca9ab;
    padding: 13px 14px;
    border-radius: 8px;
    margin-bottom: 7px;
    font-size: 14px;
    transition: 0.3s;
}

.menu a:hover,
.menu a.active {
    background: #102f32;
    color: #42e6c4;
}

.user-box {
    position: absolute;
    bottom: 20px;
    left: 18px;
    right: 18px;
    background: #0f272b;
    border: 1px solid #183c40;
    padding: 13px;
    border-radius: 10px;
}

.user-box strong {
    display: block;
    font-size: 13px;
}

.user-box span {
    color: #718e90;
    font-size: 11px;
}

/* MAIN */

.main {
    margin-left: 250px;
    padding: 35px;
    min-height: 100vh;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-title h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 30px;
}

.page-title p {
    color: #789294;
    margin-top: 6px;
    font-size: 13px;
}

.back-btn {
    text-decoration: none;
    color: #42e6c4;
    border: 1px solid #24565a;
    padding: 10px 16px;
    border-radius: 7px;
    font-size: 13px;
}

.back-btn:hover {
    background: #102f32;
}

/* TEST HEADER */

.test-header {
    background: linear-gradient(135deg, #0d2529, #0b1c20);
    border: 1px solid #194247;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.test-id-label {
    color: #789294;
    font-size: 12px;
    margin-bottom: 6px;
}

.test-id {
    color: #42e6c4;
    font-size: 25px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
}

.badges {
    display: flex;
    gap: 10px;
}

.badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.success {
    background: #103a32;
    color: #42e6c4;
}

.danger {
    background: #421d22;
    color: #ff737d;
}

.warning {
    background: #413616;
    color: #f2c94c;
}

.info {
    background: #12313b;
    color: #65c9e8;
}

/* GRID */

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.card {
    background: #0b1c20;
    border: 1px solid #16373b;
    border-radius: 13px;
    padding: 23px;
}

.card.full {
    grid-column: 1 / -1;
}

.card h3 {
    font-family: 'Space Grotesk', sans-serif;
    color: #d9eeeb;
    font-size: 17px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #17383c;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.info-item label {
    display: block;
    color: #6f8b8d;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    margin-bottom: 6px;
}

.info-item p {
    color: #dcebea;
    font-size: 14px;
    line-height: 1.5;
}

.text-box {
    background: #071417;
    border: 1px solid #153438;
    border-radius: 8px;
    padding: 15px;
    color: #c6d9d7;
    font-size: 13px;
    line-height: 1.7;
    min-height: 60px;
}

/* FOOTER */

.footer {
    margin-top: 25px;
    color: #587476;
    font-size: 11px;
    text-align: center;
}

/* RESPONSIVE */

@media (max-width: 900px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 25px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .card.full {
        grid-column: auto;
    }
}

@media (max-width: 650px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .user-box {
        position: relative;
        left: 0;
        right: 0;
        bottom: 0;
        margin-top: 20px;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .top-bar,
    .test-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">
        <h2>LAB AUTOMATION</h2>
        <p>Electrical Testing System</p>
    </div>

    <div class="menu">

        <a href="dashboard.php">Dashboard</a>

        <a href="products.php">Products</a>

        <a href="testing.php" class="active">Testing</a>

        <a href="test-types.php">Test Types</a>

        <a href="search.php">Advanced Search</a>

        <a href="reports.php">Reports</a>

        <a href="testers.php">Testers</a>

        <a href="settings.php">Settings</a>

        <a href="logout.php">Logout</a>

    </div>

    <div class="user-box">
        <strong>Lab Administrator</strong>
        <span>Administrator</span>
    </div>

</div>


<!-- MAIN -->

<div class="main">

    <div class="top-bar">

        <div class="page-title">
            <h1>Test Details</h1>
            <p>Complete laboratory testing record</p>
        </div>

        <a href="testing.php" class="back-btn">
            ← Back to Testing
        </a>

    </div>


    <!-- TEST HEADER -->

    <div class="test-header">

        <div>
            <div class="test-id-label">TEST IDENTIFICATION</div>

            <div class="test-id">
                <?php echo htmlspecialchars($test['test_id']); ?>
            </div>
        </div>

        <div class="badges">

            <span class="badge <?php echo statusClass($test['result']); ?>">
                Result:
                <?php echo htmlspecialchars($test['result']); ?>
            </span>

            <span class="badge <?php echo statusClass($test['status']); ?>">
                <?php echo htmlspecialchars($test['status']); ?>
            </span>

        </div>

    </div>


    <div class="grid">

        <!-- TEST INFORMATION -->

        <div class="card">

            <h3>Test Information</h3>

            <div class="info-grid">

                <div class="info-item">
                    <label>Test ID</label>
                    <p><?php echo htmlspecialchars($test['test_id']); ?></p>
                </div>

                <div class="info-item">
                    <label>Testing Date</label>
                    <p>
                        <?php
                        echo !empty($test['testing_date'])
                            ? date("d M Y", strtotime($test['testing_date']))
                            : "Not specified";
                        ?>
                    </p>
                </div>

                <div class="info-item">
                    <label>Test Code</label>
                    <p><?php echo htmlspecialchars($test['test_code']); ?></p>
                </div>

                <div class="info-item">
                    <label>Test Type</label>
                    <p><?php echo htmlspecialchars($test['test_name']); ?></p>
                </div>

                <div class="info-item">
                    <label>Department</label>
                    <p><?php echo htmlspecialchars($test['department']); ?></p>
                </div>

                <div class="info-item">
                    <label>Tester</label>
                    <p><?php echo htmlspecialchars($test['tester_name']); ?></p>
                </div>

                <div class="info-item">
                    <label>Designation</label>
                    <p><?php echo htmlspecialchars($test['tester_designation']); ?></p>
                </div>

            </div>

        </div>


        <!-- PRODUCT INFORMATION -->

        <div class="card">

            <h3>Product Information</h3>

            <div class="info-grid">

                <div class="info-item">
                    <label>Product ID</label>
                    <p><?php echo htmlspecialchars($test['product_id']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Name</label>
                    <p><?php echo htmlspecialchars($test['product_name']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Code</label>
                    <p><?php echo htmlspecialchars($test['product_code']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Type</label>
                    <p><?php echo htmlspecialchars($test['product_type']); ?></p>
                </div>

                <div class="info-item">
                    <label>Revision</label>
                    <p><?php echo htmlspecialchars($test['revision']); ?></p>
                </div>

            </div>

        </div>


        <!-- CRITERIA -->

        <div class="card full">

            <h3>Testing Criteria</h3>

            <div class="text-box">
                <?php
                echo !empty($test['criteria'])
                    ? nl2br(htmlspecialchars($test['criteria']))
                    : "No testing criteria provided.";
                ?>
            </div>

        </div>


        <!-- EXPECTED OUTPUT -->

        <div class="card">

            <h3>Expected Output</h3>

            <div class="text-box">
                <?php
                echo !empty($test['expected_output'])
                    ? nl2br(htmlspecialchars($test['expected_output']))
                    : "No expected output provided.";
                ?>
            </div>

        </div>


        <!-- ACTUAL OUTPUT -->

        <div class="card">

            <h3>Actual Output</h3>

            <div class="text-box">
                <?php
                echo !empty($test['actual_output'])
                    ? nl2br(htmlspecialchars($test['actual_output']))
                    : "No actual output provided.";
                ?>
            </div>

        </div>


        <!-- REMARKS -->

        <div class="card full">

            <h3>Testing Remarks</h3>

            <div class="text-box">
                <?php
                echo !empty($test['remarks'])
                    ? nl2br(htmlspecialchars($test['remarks']))
                    : "No remarks provided.";
                ?>
            </div>

        </div>

    </div>


    <div class="footer">
        Lab Automation System © 2026 | Electrical Testing Laboratory
    </div>

</div>

</body>
</html>