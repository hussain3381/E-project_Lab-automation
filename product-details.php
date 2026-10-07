<?php
include "db.php";
require_page_access(__FILE__);
require_once __DIR__ . '/models/Tester.php';

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
    http_response_code(404);
    exit('Product not found.');
}

$isTester = (string) ($_SESSION['role'] ?? '') === 'Tester';
$testerProfileId = 0;
if ($isTester) {
    $testerProfile = Tester::findByUserId($conn, (int) ($_SESSION['user_id'] ?? 0));
    if ($testerProfile === null) {
        RoleMiddleware::handle([]);
    }
    $testerProfileId = (int) $testerProfile['id'];
    $productAccess = $conn->prepare(
        'SELECT 1 FROM tests AS assigned_test WHERE assigned_test.product_id = ? AND (' .
        'assigned_test.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS assigned_participant ' .
        'WHERE assigned_participant.test_record_id = assigned_test.id AND assigned_participant.tester_id = ?)) LIMIT 1'
    );
    $productAccess->bind_param('sii', $product['product_id'], $testerProfileId, $testerProfileId);
    $productAccess->execute();
    $isAssignedProduct = $productAccess->get_result()->num_rows > 0;
    $productAccess->close();
    if (!$isAssignedProduct) {
        RoleMiddleware::handle([]);
    }
}

/* Testers can review only the records attached to their own tester profile. */
$historySql = "SELECT
        tests.*,
        test_types.test_name,
        test_types.test_code,
        COALESCE(routed_department.department_name, test_types.department) AS department_name,
        COALESCE((
            SELECT GROUP_CONCAT(DISTINCT participant.name ORDER BY participant.name SEPARATOR ', ')
            FROM test_participants AS participation
            INNER JOIN testers AS participant ON participant.id = participation.tester_id
            WHERE participation.test_record_id = tests.id
        ), testers.name, 'Not Assigned') AS tester_names
     FROM tests
     LEFT JOIN test_types ON tests.test_type_id = test_types.id
     LEFT JOIN departments AS routed_department ON tests.department_id = routed_department.id
     LEFT JOIN testers ON tests.tester_id = testers.id
     WHERE tests.product_id = ?";
if ($isTester) {
    $historySql .= ' AND (tests.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS own_participant '
        . 'WHERE own_participant.test_record_id = tests.id AND own_participant.tester_id = ?))';
}
$historySql .= ' ORDER BY tests.id DESC';
$history_stmt = $conn->prepare($historySql);
if ($isTester) {
    $history_stmt->bind_param('sii', $product['product_id'], $testerProfileId, $testerProfileId);
} else {
    $history_stmt->bind_param('s', $product['product_id']);
}
$history_stmt->execute();
$history = $history_stmt->get_result();

$event_stmt = $conn->prepare(
    'SELECT event_type, old_status, new_status, notes, changed_by, changed_at ' .
    'FROM product_workflow_events WHERE product_id = ? ORDER BY id DESC'
);
$event_stmt->bind_param('s', $product['product_id']);
$event_stmt->execute();
$workflowEvents = $event_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Details | Lab Automation</title>
<link rel="stylesheet" href="assets/css/pages/product-details.css">

</head>

<body>


<!-- SIDEBAR -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>


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

        if (in_array($status, ["Passed", "CPRI Ready", "Handed to CPRI"], true)) {
            $status_class = "pass";
        }

        if (str_contains($status, "Failed")) {
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
                    <?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?>
                </div>

            </div>

            <div class="detail">
                <label>Next Test Cycle</label>
                <div class="detail-value"><?php echo (int) ($product['rework_cycle'] ?? 0) + 1; ?></div>
            </div>

            <div class="detail">
                <label>CPRI Handoff Status</label>
                <div class="detail-value"><?php echo htmlspecialchars((string) ($product['cpri_status'] ?? 'Not Ready'), ENT_QUOTES, 'UTF-8'); ?></div>
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

                            <th>Department</th>

                            <th>Cycle</th>

                            <th>Tester(s)</th>

                            <th>Date</th>

                            <th>Result</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while ($test = mysqli_fetch_assoc($history)): ?>

                            <tr>

                                <td>
                                    <a class="test-id" href="test-details.php?id=<?php echo (int) $test['id']; ?>">
                                        <?php echo htmlspecialchars($test['test_id'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $test['test_name'] ?? 'N/A'
                                    );
                                    ?>

                                </td>


                                <td><?php echo htmlspecialchars((string) ($test['department_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo (int) ($test['cycle_number'] ?? 1); ?></td>

                                <td><?php echo htmlspecialchars((string) ($test['tester_names'] ?? 'Not Assigned'), ENT_QUOTES, 'UTF-8'); ?></td>


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

    <!-- WORKFLOW EVENTS: re-manufacture release and manual external CPRI handoff audit. -->
    <div class="card">
        <div class="card-title">Product <span>Workflow Events</span></div>
        <div class="table-wrapper">
            <?php if ($workflowEvents->num_rows > 0): ?>
                <table>
                    <thead><tr><th>Event</th><th>Status change</th><th>Notes</th><th>Recorded by</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php while ($event = $workflowEvents->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['event_type'] === 'CPRI_HANDOFF' ? 'CPRI handoff' : 'Re-manufacture release', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($event['old_status'] ?? '—') . ' → ' . $event['new_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars((string) ($event['notes'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><?php echo htmlspecialchars($event['changed_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($event['changed_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty"><p>No re-manufacture or CPRI workflow events recorded.</p></div>
            <?php endif; ?>
        </div>
    </div>

</main>

</body>

</html>