<?php

include "db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product request.");
}

$id = intval($_GET['id']);

$message = "";
$message_type = "";

/* =========================
   UPDATE PRODUCT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_code = trim($_POST['product_code']);
    $product_name = trim($_POST['product_name']);
    $product_type = trim($_POST['product_type']);
    $revision = trim($_POST['revision']);
    $manufacturing_number = trim($_POST['manufacturing_number']);
    $manufacturing_date = $_POST['manufacturing_date'];
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if (
        empty($product_code) ||
        empty($product_name) ||
        empty($manufacturing_number)
    ) {

        $message = "Please fill all required fields.";
        $message_type = "error";

    } else {

        $sql = "UPDATE products SET
                product_code = ?,
                product_name = ?,
                product_type = ?,
                revision = ?,
                manufacturing_number = ?,
                manufacturing_date = ?,
                description = ?,
                status = ?
                WHERE id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssi",
                $product_code,
                $product_name,
                $product_type,
                $revision,
                $manufacturing_number,
                $manufacturing_date,
                $description,
                $status,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Product updated successfully!";
                $message_type = "success";

            } else {

                $message = "Error updating product: " . mysqli_stmt_error($stmt);
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);

        } else {

            $message = "Database error: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}


/* =========================
   GET PRODUCT DATA
========================= */

$sql = "SELECT * FROM products WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

if (!$product) {
    die("Product not found.");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Product | Lab Automation</title>

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
    margin-bottom: 28px;
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

/* FORM CARD */

.form-card {
    max-width: 950px;
    background: #0b1c20;
    border: 1px solid #16373b;
    border-radius: 14px;
    padding: 28px;
}

.form-card h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 19px;
    margin-bottom: 6px;
}

.form-subtitle {
    color: #6f8b8d;
    font-size: 12px;
    margin-bottom: 25px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    color: #a9c0bf;
    font-size: 12px;
    margin-bottom: 8px;
}

.form-group label span {
    color: #42e6c4;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    background: #071417;
    border: 1px solid #1b4145;
    color: #e5f1ef;
    padding: 12px 13px;
    border-radius: 7px;
    outline: none;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #42e6c4;
    box-shadow: 0 0 0 2px rgba(66, 230, 196, 0.08);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group select option {
    background: #0b1c20;
}

/* PRODUCT ID */

.product-id-box {
    background: #102b2f;
    border: 1px solid #1b4b50;
    color: #42e6c4;
    padding: 12px 13px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
}

/* MESSAGE */

.message {
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 22px;
    font-size: 13px;
}

.message.success {
    background: #103a32;
    border: 1px solid #205e51;
    color: #42e6c4;
}

.message.error {
    background: #3b1c20;
    border: 1px solid #673037;
    color: #ff858c;
}

/* BUTTONS */

.form-actions {
    margin-top: 25px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.cancel-btn {
    text-decoration: none;
    color: #9bb1b0;
    border: 1px solid #284448;
    padding: 12px 20px;
    border-radius: 7px;
    font-size: 13px;
}

.cancel-btn:hover {
    background: #10272b;
}

.update-btn {
    border: none;
    background: #42e6c4;
    color: #071417;
    padding: 12px 22px;
    border-radius: 7px;
    font-weight: 700;
    cursor: pointer;
    font-size: 13px;
}

.update-btn:hover {
    background: #6ff0d5;
}

/* RESPONSIVE */

@media (max-width: 850px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 25px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
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

    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
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

        <a href="testers.php">
            Testers
        </a>

        <a href="settings.php">
            Settings
        </a>

        <a href="logout.php">
            Logout
        </a>

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

            <h1>Edit Product</h1>

            <p>
                Update registered product information
            </p>

        </div>


        <a href="products.php" class="back-btn">
            ← Back to Products
        </a>

    </div>


    <div class="form-card">

        <h2>Product Information</h2>

        <p class="form-subtitle">
            Modify the required product details and save the changes.
        </p>


        <?php if (!empty($message)): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-grid">


                <!-- PRODUCT ID -->

                <div class="form-group">

                    <label>
                        Product ID
                    </label>

                    <div class="product-id-box">

                        <?php echo htmlspecialchars($product['product_id']); ?>

                    </div>

                </div>


                <!-- PRODUCT CODE -->

                <div class="form-group">

                    <label>
                        Product Code <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="product_code"
                        value="<?php echo htmlspecialchars($product['product_code']); ?>"
                        required
                    >

                </div>


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label>
                        Product Name <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        value="<?php echo htmlspecialchars($product['product_name']); ?>"
                        required
                    >

                </div>


                <!-- PRODUCT TYPE -->

                <div class="form-group">

                    <label>
                        Product Type
                    </label>

                    <input
                        type="text"
                        name="product_type"
                        value="<?php echo htmlspecialchars($product['product_type']); ?>"
                    >

                </div>


                <!-- REVISION -->

                <div class="form-group">

                    <label>
                        Revision
                    </label>

                    <input
                        type="text"
                        name="revision"
                        value="<?php echo htmlspecialchars($product['revision']); ?>"
                    >

                </div>


                <!-- MANUFACTURING NUMBER -->

                <div class="form-group">

                    <label>
                        Manufacturing Number <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="manufacturing_number"
                        value="<?php echo htmlspecialchars($product['manufacturing_number']); ?>"
                        required
                    >

                </div>


                <!-- MANUFACTURING DATE -->

                <div class="form-group">

                    <label>
                        Manufacturing Date
                    </label>

                    <input
                        type="date"
                        name="manufacturing_date"
                        value="<?php echo htmlspecialchars($product['manufacturing_date']); ?>"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Product Status
                    </label>

                    <select name="status">

                        <option value="Pending Testing"
                            <?php
                            if ($product['status'] == 'Pending Testing') {
                                echo 'selected';
                            }
                            ?>>
                            Pending Testing
                        </option>

                        <option value="Testing In Progress"
                            <?php
                            if ($product['status'] == 'Testing In Progress') {
                                echo 'selected';
                            }
                            ?>>
                            Testing In Progress
                        </option>

                        <option value="Passed"
                            <?php
                            if ($product['status'] == 'Passed') {
                                echo 'selected';
                            }
                            ?>>
                            Passed
                        </option>

                        <option value="Failed - Re-manufacturing"
                            <?php
                            if ($product['status'] == 'Failed - Re-manufacturing') {
                                echo 'selected';
                            }
                            ?>>
                            Failed - Re-manufacturing
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Product Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Enter product description..."
                    ><?php echo htmlspecialchars($product['description']); ?></textarea>

                </div>


            </div>


            <div class="form-actions">

                <a
                    href="products.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="update-btn"
                >
                    Update Product
                </button>

            </div>


        </form>

    </div>

</div>

</body>

</html>