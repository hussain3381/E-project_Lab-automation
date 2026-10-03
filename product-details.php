<?php
include "db.php";

/* Product ID from URL */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product request.");
}

$id = intval($_GET['id']);

/* Get Product */
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    die("Product not found.");
}

/* Get Testing History */
$history_stmt = mysqli_prepare(
    $conn,
    "SELECT
        tests.*,
        test_types.test_name,
        test_types.test_code,
        testers.name AS tester_name
     FROM tests
     LEFT JOIN test_types
        ON tests.test_type_id = test_types.id
     LEFT JOIN testers
        ON tests.tester_id = testers.id
     WHERE tests.product_id = ?
     ORDER BY tests.id DESC"
);

mysqli_stmt_bind_param(
    $history_stmt,
    "s",
    $product['product_id']
);

mysqli_stmt_execute($history_stmt);

$history = mysqli_stmt_get_result($history_stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Details | Lab Automation</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #071111;
            color: #e9ffff;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 245px;
            height: 100vh;
            background: #0b1718;
            border-right: 1px solid rgba(76,255,218,0.12);
            padding: 28px 18px;
        }

        .logo {
            padding: 0 12px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 25px;
        }

        .logo h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 21px;
            letter-spacing: 1px;
        }

        .logo span {
            color: #4cffda;
        }

        .logo p {
            font-size: 11px;
            color: #759090;
            margin-top: 6px;
        }

        .nav-title {
            font-size: 10px;
            color: #5e7777;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 20px 12px 10px;
        }

        .nav a {
            display: block;
            text-decoration: none;
            color: #8fa6a6;
            padding: 12px 13px;
            margin: 5px 0;
            border-radius: 9px;
            font-size: 13px;
            transition: 0.25s;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(76,255,218,0.08);
            color: #4cffda;
        }

        /* MAIN */

        .main {
            margin-left: 245px;
            padding: 35px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .top h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 27px;
        }

        .top p {
            color: #718989;
            font-size: 13px;
            margin-top: 6px;
        }

        .back-btn {
            text-decoration: none;
            color: #4cffda;
            border: 1px solid rgba(76,255,218,0.20);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
        }

        .back-btn:hover {
            background: rgba(76,255,218,0.08);
        }

        /* PRODUCT HEADER */

        .product-header {
            background: linear-gradient(
                135deg,
                #0d2021,
                #0b1819
            );

            border: 1px solid rgba(76,255,218,0.14);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 22px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-title {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .product-icon {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            background: rgba(76,255,218,0.08);
            border: 1px solid rgba(76,255,218,0.15);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .product-title h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px;
        }

        .product-title p {
            color: #4cffda;
            font-size: 12px;
            margin-top: 5px;
        }

        .status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .pending {
            color: #f5c86b;
            background: rgba(245,200,107,0.08);
            border: 1px solid rgba(245,200,107,0.15);
        }

        .pass {
            color: #6ff0b5;
            background: rgba(48,220,150,0.08);
            border: 1px solid rgba(48,220,150,0.15);
        }

        .fail {
            color: #ff8989;
            background: rgba(255,80,80,0.08);
            border: 1px solid rgba(255,80,80,0.15);
        }

        /* CARDS */

        .card {
            background: #0c1a1b;
            border: 1px solid rgba(76,255,218,0.11);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 22px;
        }

        .card-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            margin-bottom: 22px;
        }

        .card-title span {
            color: #4cffda;
        }

        /* DETAILS GRID */

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .detail {
            background: #091516;
            border: 1px solid rgba(255,255,255,0.06);
            padding: 16px;
            border-radius: 9px;
        }

        .detail label {
            display: block;
            color: #627979;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 7px;
        }

        .detail-value {
            color: #d9eeee;
            font-size: 13px;
            font-weight: 500;
            word-break: break-word;
        }

        .product-id {
            color: #4cffda;
            font-weight: 600;
        }

        .description {
            margin-top: 18px;
            background: #091516;
            border: 1px solid rgba(255,255,255,0.06);
            padding: 18px;
            border-radius: 9px;
        }

        .description label {
            color: #627979;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .description p {
            color: #a6baba;
            font-size: 13px;
            line-height: 1.7;
            margin-top: 8px;
        }

        /* TEST HISTORY */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            text-align: left;
            padding: 14px;
            background: #091516;
            color: #627979;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        td {
            padding: 15px 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
            color: #a7baba;
            font-size: 12px;
        }

        tr:hover td {
            background: rgba(76,255,218,0.025);
        }

        .test-id {
            color: #4cffda;
            font-weight: 600;
        }

        .result-pass {
            color: #6ff0b5;
        }

        .result-fail {
            color: #ff8989;
        }

        .result-pending {
            color: #f5c86b;
        }

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #718989;
            font-size: 13px;
        }

        .empty-icon {
            font-size: 30px;
            margin-bottom: 12px;
        }

        /* RESPONSIVE */

        @media (max-width: 950px) {

            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 850px) {

            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
                padding: 25px;
            }

        }

        @media (max-width: 650px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }

            .top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .product-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">

        <h1>LAB <span>AUTOMATION</span></h1>

        <p>Electrical Testing System</p>

    </div>


    <div class="nav-title">
        Main Menu
    </div>

    <nav class="nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php" class="active">
            Products
        </a>

        <a href="testing.php">
            Testing
        </a>

        <a href="test-types.php">
            Test Types
        </a>

        <a href="search.php">
            Advanced Search
        </a>

        <a href="reports.php">
            Reports
        </a>

    </nav>


    <div class="nav-title">
        Management
    </div>

    <nav class="nav">

        <a href="testers.php">
            Testers
        </a>

        <a href="settings.php">
            Settings
        </a>

        <a href="login.php">
            Logout
        </a>

    </nav>

</aside>


<!-- MAIN -->

<main class="main">


    <div class="top">

        <div>

            <h2>Product Details</h2>

            <p>
                Complete product information and testing history.
            </p>

        </div>


        <a href="products.php" class="back-btn">
            ← Back to Products
        </a>

    </div>


    <!-- PRODUCT HEADER -->

    <div class="product-header">

        <div class="product-title">

            <div class="product-icon">
                ⚡
            </div>

            <div>

                <h1>
                    <?php echo htmlspecialchars($product['product_name']); ?>
                </h1>

                <p>
                    Product ID:
                    <?php echo htmlspecialchars($product['product_id']); ?>
                </p>

            </div>

        </div>


        <?php

        $status = $product['status'];

        $status_class = "pending";

        if ($status == "Passed") {
            $status_class = "pass";
        }

        if ($status == "Failed") {
            $status_class = "fail";
        }

        ?>

        <span class="status <?php echo $status_class; ?>">

            <?php echo htmlspecialchars($status); ?>

        </span>

    </div>


    <!-- PRODUCT INFORMATION -->

    <div class="card">

        <div class="card-title">
            Product <span>Information</span>
        </div>


        <div class="details-grid">


            <div class="detail">

                <label>Product ID</label>

                <div class="detail-value product-id">

                    <?php
                    echo htmlspecialchars($product['product_id']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Product Code</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['product_code']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Product Name</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['product_name']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Product Type</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['product_type']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Revision</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['revision']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Manufacturing Number</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['manufacturing_number']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Manufacturing Date</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['manufacturing_date']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Registered On</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['created_at']);
                    ?>

                </div>

            </div>


            <div class="detail">

                <label>Current Status</label>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars($product['status']);
                    ?>

                </div>

            </div>


        </div>


        <div class="description">

            <label>Description</label>

            <p>

                <?php

                if (!empty($product['description'])) {

                    echo nl2br(
                        htmlspecialchars($product['description'])
                    );

                } else {

                    echo "No description has been added for this product.";

                }

                ?>

            </p>

        </div>

    </div>


    <!-- TESTING HISTORY -->

    <div class="card">

        <div class="card-title">
            Testing <span>History</span>
        </div>


        <div class="table-wrapper">

            <?php if (mysqli_num_rows($history) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>Test ID</th>

                            <th>Test Type</th>

                            <th>Tester</th>

                            <th>Date</th>

                            <th>Result</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while ($test = mysqli_fetch_assoc($history)): ?>

                            <tr>

                                <td>

                                    <span class="test-id">

                                        <?php
                                        echo htmlspecialchars($test['test_id']);
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $test['test_name'] ?? 'N/A'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $test['tester_name'] ?? 'Not Assigned'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $test['testing_date'] ?? 'N/A'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $result_value = $test['result'];

                                    if ($result_value == "PASS") {

                                        echo '<span class="result-pass">PASS</span>';

                                    } elseif ($result_value == "FAIL") {

                                        echo '<span class="result-fail">FAIL</span>';

                                    } else {

                                        echo '<span class="result-pending">PENDING</span>';

                                    }

                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $test['status'] ?? 'Pending'
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    <div class="empty-icon">
                        🧪
                    </div>

                    <p>
                        No testing records available for this product yet.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>


</main>

</body>

</html>