<?php
// Manage which test types apply to each product family and which are mandatory for CPRI readiness.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/controllers/ProductTestPlanController.php';

$planState = product_test_plan_save($conn);
$productTypes = ProductCatalog::activeTypes($conn);
$selectedTypeId = (int) ($_POST['product_type_id'] ?? $_GET['product_type_id'] ?? ($productTypes[0]['id'] ?? 0));
$testTypes = $conn->query(
    'SELECT id, test_code, numeric_code, test_name, department FROM test_types WHERE is_active = 1 ORDER BY department, test_name'
)->fetch_all(MYSQLI_ASSOC);
$existingMap = [];
if ($selectedTypeId > 0) {
    $mapStatement = $conn->prepare(
        'SELECT test_type_id, is_required FROM product_type_test_types WHERE product_type_id = ? ORDER BY sequence_no'
    );
    $mapStatement->bind_param('i', $selectedTypeId);
    $mapStatement->execute();
    $mapResult = $mapStatement->get_result();
    while ($map = $mapResult->fetch_assoc()) {
        $existingMap[(int) $map['test_type_id']] = (int) $map['is_required'];
    }
    $mapStatement->close();
}
require __DIR__ . '/views/product-test-plan/index.php';
