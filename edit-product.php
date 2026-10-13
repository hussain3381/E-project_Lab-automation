<?php
// Keep generated identity and workflow status immutable on the generic edit form.
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_page_access(__FILE__);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit("Invalid product request.");
}

$id = (int) $_GET['id'];
$message = "";
$message_type = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $product_name = trim((string) ($_POST['product_name'] ?? ''));
    $manufacturing_date = trim((string) ($_POST['manufacturing_date'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $dateValue = DateTime::createFromFormat('!Y-m-d', $manufacturing_date);
    $validDate = $dateValue !== false && $dateValue->format('Y-m-d') === $manufacturing_date;

    if ($product_name === '' || strlen($product_name) > 180 || !$validDate) {
        $message = "Enter a product name and a valid manufacturing date.";
        $message_type = "error";
    } else {
        try {
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT id, product_id, product_name, manufacturing_date, description FROM products WHERE id = ? FOR UPDATE');
            $lock->bind_param('i', $id);
            $lock->execute();
            $before = $lock->get_result()->fetch_assoc();
            $lock->close();
            if ($before === null) {
                throw new DomainException('The selected product no longer exists.');
            }
            $update = $conn->prepare(
                'UPDATE products SET product_name = ?, manufacturing_date = ?, description = ? WHERE id = ?'
            );
            $update->bind_param('sssi', $product_name, $manufacturing_date, $description, $id);
            $update->execute();
            $update->close();
            audit_write($conn, 'product_updated', 'Product', (string) $before['product_id'], 'Product details were corrected; generated identity and workflow state were preserved.', [
                'product_name_before' => (string) $before['product_name'],
                'product_name_after' => $product_name,
                'manufacturing_date_before' => (string) $before['manufacturing_date'],
                'manufacturing_date_after' => $manufacturing_date,
                'description_changed' => (string) ($before['description'] ?? '') !== $description,
            ]);
            $conn->commit();
            $message = 'Product details updated. Generated identity and workflow status were not changed.';
            $message_type = 'success';
        } catch (DomainException $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the validation message. */ }
            $message = $exception->getMessage();
            $message_type = 'error';
        } catch (Throwable $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
            error_log('Product update failed: ' . $exception->getMessage());
            $message = 'Product details could not be saved. Please retry.';
            $message_type = 'error';
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    exit("Product not found.");
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

<title>Edit Product | Lab Automation</title>
<link rel="stylesheet" href="assets/css/pages/edit-product.css">

</head>

<body>


<!-- SIDEBAR -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>


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
            Update descriptive information. The generated Product ID and workflow state stay locked for traceability.
        </p>


        <?php if (!empty($message)): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">


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

                    <div class="product-id-box"><?php echo htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8'); ?></div>

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

                    <div class="product-id-box"><?php echo htmlspecialchars($product['product_type'], ENT_QUOTES, 'UTF-8'); ?></div>

                </div>


                <!-- REVISION -->

                <div class="form-group">

                    <label>
                        Revision
                    </label>

                    <div class="product-id-box"><?php echo htmlspecialchars($product['revision'], ENT_QUOTES, 'UTF-8'); ?></div>

                </div>


                <!-- MANUFACTURING NUMBER -->

                <div class="form-group">

                    <label>
                        Manufacturing Number <span>*</span>
                    </label>

                    <div class="product-id-box"><?php echo htmlspecialchars($product['manufacturing_number'], ENT_QUOTES, 'UTF-8'); ?></div>

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


                <!-- STATUS is changed only by a test result or the workflow controls. -->
                <div class="form-group">
                    <label>Product Workflow Status</label>
                    <div class="product-id-box"><?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?></div>
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