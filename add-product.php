<?php
include "db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_code = trim($_POST["product_code"]);
    $product_name = trim($_POST["product_name"]);
    $product_type = trim($_POST["product_type"]);
    $revision = trim($_POST["revision"]);
    $manufacturing_number = trim($_POST["manufacturing_number"]);
    $manufacturing_date = $_POST["manufacturing_date"];
    $description = trim($_POST["description"]);

    if (
        empty($product_code) ||
        empty($product_name) ||
        empty($product_type) ||
        empty($revision) ||
        empty($manufacturing_number) ||
        empty($manufacturing_date)
    ) {
        $message = "Please fill all required fields.";
        $message_type = "error";
    } else {

        /*
         Product ID:
         Product Code + Revision + Manufacturing Number
         Example: 1234 + 01 + 567890 = 123401567890
         Database field currently accepts 10 characters,
         so we generate a unique 10-digit numeric ID.
        */

        $product_id = str_pad(
            preg_replace('/\D/', '', $manufacturing_number),
            10,
            "0",
            STR_PAD_LEFT
        );

        // Make sure Product ID is exactly 10 digits
        $product_id = substr($product_id, 0, 10);

        // Check duplicate Product ID
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM products WHERE product_id = ?"
        );

        mysqli_stmt_bind_param($check, "s", $product_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "This Product ID already exists. Please use a different manufacturing number.";
            $message_type = "error";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO products
                (product_id, product_code, product_name, product_type,
                revision, manufacturing_number, manufacturing_date,
                description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending Testing')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssss",
                $product_id,
                $product_code,
                $product_name,
                $product_type,
                $revision,
                $manufacturing_number,
                $manufacturing_date,
                $description
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Product added successfully! Product ID: " . $product_id;
                $message_type = "success";

                $_POST = array();

            } else {

                $message = "Error: " . mysqli_error($conn);
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Lab Automation</title>

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

        /* Sidebar */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 245px;
            height: 100vh;
            background: #0b1718;
            border-right: 1px solid rgba(76, 255, 218, 0.12);
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
            background: rgba(76, 255, 218, 0.08);
            color: #4cffda;
        }

        /* Main */

        .main {
            margin-left: 245px;
            padding: 35px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
            border: 1px solid rgba(76,255,218,0.25);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
        }

        .back-btn:hover {
            background: rgba(76,255,218,0.08);
        }

        /* Form Card */

        .form-card {
            max-width: 1050px;
            background: #0c1a1b;
            border: 1px solid rgba(76,255,218,0.12);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 17px;
            margin-bottom: 22px;
            color: #ffffff;
        }

        .section-title span {
            color: #4cffda;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 12px;
            color: #9bb0b0;
            margin-bottom: 8px;
        }

        label span {
            color: #4cffda;
        }

        input,
        select,
        textarea {
            width: 100%;
            background: #081415;
            border: 1px solid rgba(255,255,255,0.10);
            color: #eaffff;
            padding: 13px 14px;
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: 0.25s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #4cffda;
            box-shadow: 0 0 0 3px rgba(76,255,218,0.06);
        }

        select option {
            background: #0c1a1b;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .info-box {
            margin-top: 25px;
            padding: 14px 16px;
            background: rgba(76,255,218,0.04);
            border: 1px solid rgba(76,255,218,0.10);
            border-radius: 9px;
            color: #88a0a0;
            font-size: 12px;
            line-height: 1.6;
        }

        .info-box strong {
            color: #4cffda;
        }

        .message {
            max-width: 1050px;
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .success {
            background: rgba(48, 220, 150, 0.08);
            border: 1px solid rgba(48, 220, 150, 0.25);
            color: #6ff0b5;
        }

        .error {
            background: rgba(255, 80, 80, 0.08);
            border: 1px solid rgba(255, 80, 80, 0.25);
            color: #ff8989;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            border: none;
            padding: 13px 22px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel {
            background: #152324;
            color: #91a7a7;
            text-decoration: none;
        }

        .btn-save {
            background: #4cffda;
            color: #061010;
        }

        .btn-save:hover {
            box-shadow: 0 0 22px rgba(76,255,218,0.25);
            transform: translateY(-1px);
        }

        /* Responsive */

        @media (max-width: 850px) {

            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
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

        }

    </style>

</head>

<body>

    <!-- Sidebar -->

    <aside class="sidebar">

        <div class="logo">
            <h1>LAB <span>AUTOMATION</span></h1>
            <p>Electrical Testing System</p>
        </div>

        <div class="nav-title">Main Menu</div>

        <nav class="nav">

            <a href="dashboard.php">Dashboard</a>

            <a href="products.php" class="active">Products</a>

            <a href="testing.php">Testing</a>

            <a href="test-types.php">Test Types</a>

            <a href="search.php">Advanced Search</a>

            <a href="reports.php">Reports</a>

        </nav>

        <div class="nav-title">Management</div>

        <nav class="nav">

            <a href="testers.php">Testers</a>

            <a href="settings.php">Settings</a>

            <a href="login.php">Logout</a>

        </nav>

    </aside>


    <!-- Main Content -->

    <main class="main">

        <div class="top">

            <div>
                <h2>Add New Product</h2>
                <p>Register a newly manufactured product in the laboratory system.</p>
            </div>

            <a href="products.php" class="back-btn">
                ← Back to Products
            </a>

        </div>


        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <div class="form-card">

            <div class="section-title">
                Product <span>Information</span>
            </div>

            <form method="POST">

                <div class="form-grid">

                    <div class="field">

                        <label>
                            Product Code <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="product_code"
                            placeholder="e.g. SWG01"
                            value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Product Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="product_name"
                            placeholder="e.g. 100A Switch Gear"
                            value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Product Type <span>*</span>
                        </label>

                        <select name="product_type" required>

                            <option value="">Select Product Type</option>

                            <option value="Switch Gear">Switch Gear</option>
                            <option value="Fuse">Fuse</option>
                            <option value="Capacitor">Capacitor</option>
                            <option value="Resistor">Resistor</option>
                            <option value="Circuit Breaker">Circuit Breaker</option>
                            <option value="Other">Other</option>

                        </select>

                    </div>


                    <div class="field">

                        <label>
                            Revision <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="revision"
                            placeholder="e.g. R01"
                            value="<?php echo htmlspecialchars($_POST['revision'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Manufacturing Number <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="manufacturing_number"
                            placeholder="Enter manufacturing number"
                            value="<?php echo htmlspecialchars($_POST['manufacturing_number'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Manufacturing Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="manufacturing_date"
                            value="<?php echo htmlspecialchars($_POST['manufacturing_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="field full">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            placeholder="Enter product details, specifications or additional information..."
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                    </div>

                </div>


                <div class="info-box">

                    <strong>Testing Flow:</strong>
                    After registration, the product will receive the status
                    <strong>Pending Testing</strong>.
                    You can then assign the required tests from the Testing module.

                </div>


                <div class="actions">

                    <a href="products.php" class="btn btn-cancel">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-save">
                        + Save Product
                    </button>

                </div>

            </form>

        </div>

    </main>

</body>

</html>