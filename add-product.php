<?php
// Validate product registration and build its ID from the selected sprint mapping.
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_roles(['Administrator', 'Lab Manager']);
require_once __DIR__ . "/models/ProductIdGenerator.php";
require_once __DIR__ . "/models/ProductCode.php";
require_once __DIR__ . "/models/ProductCatalog.php";

$message = "";
$message_type = "";
$productTypes = ProductCatalog::activeTypes($conn);
$productCodes = ProductCatalog::allCodes($conn);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $product_code = strtoupper(trim((string) ($_POST["product_code"] ?? "")));
    $product_type_id = (int) ($_POST["product_type_id"] ?? 0);
    $product_name = trim((string) ($_POST["product_name"] ?? ""));
    $revision = trim((string) ($_POST["revision"] ?? ""));
    $manufacturing_number = trim((string) ($_POST["manufacturing_number"] ?? ""));
    $manufacturing_date = trim((string) ($_POST["manufacturing_date"] ?? ""));
    $description = trim((string) ($_POST["description"] ?? ""));

    if ($product_code === "" || $product_type_id < 1 || $product_name === "" || $revision === "" || $manufacturing_number === "" || $manufacturing_date === "") {
        $message = "Please fill all required fields.";
        $message_type = "error";
    } else {
        $family = ProductCatalog::findActiveType($conn, $product_type_id);
        $codeMapping = ProductCode::findActiveByCode($conn, $product_code);

        if ($family === null) {
            $message = "Select an active product family.";
            $message_type = "error";
        } elseif ($codeMapping === null) {
            $message = "This product code/model is not registered. Ask an Administrator to add it in Product Catalog.";
            $message_type = "error";
        } elseif ((int) $codeMapping['product_type_id'] !== $product_type_id) {
            $message = "The selected family does not match the registered product code/model.";
            $message_type = "error";
        } else {
            try {
                $product_id = ProductIdGenerator::generate(
                    (string) $codeMapping['numeric_code'],
                    $revision,
                    $manufacturing_number
                );

                $check = $conn->prepare("SELECT id FROM products WHERE product_id = ? LIMIT 1");
                $check->bind_param("s", $product_id);
                $check->execute();
                $duplicate = $check->get_result()->num_rows > 0;
                $check->close();

                if ($duplicate) {
                    $message = "This Product ID already exists. Check the revision or manufacturing number.";
                    $message_type = "error";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO products
                        (product_id, product_code, product_code_id, product_code_numeric,
                         product_name, product_type, product_type_id, revision,
                         manufacturing_number, manufacturing_date, description, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Testing')"
                    );
                    $numericCode = (string) $codeMapping['numeric_code'];
                    $familyName = (string) $family['type_name'];
                    $codeId = (int) $codeMapping['id'];
                    $stmt->bind_param(
                        "ssisssissss",
                        $product_id,
                        $product_code,
                        $codeId,
                        $numericCode,
                        $product_name,
                        $familyName,
                        $product_type_id,
                        $revision,
                        $manufacturing_number,
                        $manufacturing_date,
                        $description
                    );

                    try {
                        $stmt->execute();
                        $message = "Product added successfully! Product ID: " . $product_id;
                        $message_type = "success";
                        $_POST = [];
                    } catch (mysqli_sql_exception $exception) {
                        error_log('Lab Automation product insert failed: ' . $exception->getMessage());
                        $message = "The Product ID or manufacturing record already exists. Check the entered numbers.";
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            } catch (InvalidArgumentException $exception) {
                $message = $exception->getMessage();
                $message_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Lab Automation</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/pages/add-product.css">

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
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-grid">

                    <div class="field">

                        <label>
                            Product Code <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="product_code"
                            list="product-code-options"
                            placeholder="e.g. SWG01"
                            value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>"
                            required
                        >
                        <datalist id="product-code-options">
                            <?php foreach ($productCodes as $codeOption): ?>
                                <option value="<?php echo htmlspecialchars($codeOption['product_code'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endforeach; ?>
                        </datalist>

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
                            Product Family <span>*</span>
                        </label>

                        <select name="product_type_id" required>
                            <option value="">Select Product Family</option>
                            <?php foreach ($productTypes as $typeOption): ?>
                                <option value="<?php echo (int) $typeOption['id']; ?>" <?php echo (int) ($_POST['product_type_id'] ?? 0) === (int) $typeOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($typeOption['type_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>


                    <div class="field">

                        <label>
                            Revision Code (2 digits) <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="revision"
                            placeholder="e.g. 01"
                            inputmode="numeric"
                            pattern="[0-9]{2}"
                            maxlength="2"
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
                            placeholder="1 to 6 digits; padded to 6"
                            inputmode="numeric"
                            pattern="[0-9]{1,6}"
                            maxlength="6"
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
                    Product ID is generated as <strong>2-digit product-code ID + 2-digit revision + 6-digit manufacturing number</strong>.
                    The exact code/model must first be mapped by an Administrator in Product Catalog.

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