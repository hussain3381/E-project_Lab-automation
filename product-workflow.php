<?php
// Product workflow control center: rework release and gated manual CPRI handoff.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_roles(['Administrator', 'Lab Manager', 'Quality Control']);
require_once __DIR__ . '/controllers/ProductWorkflowController.php';

$workflowState = product_workflow_handle_request($conn);
$productRows = $conn->query(
    'SELECT p.product_id, p.product_name, p.product_type, p.product_type_id, p.status, p.rework_cycle, ' .
    'p.cpri_status, p.cpri_handoff_at, p.cpri_reference, p.cpri_handoff_by, h.test_cycle AS last_handoff_cycle, ' .
    'h.handed_off_at AS last_handoff_at, h.reference AS last_handoff_reference, ' .
    'h.handed_off_by AS last_handoff_by ' .
    'FROM products AS p LEFT JOIN cpri_handoffs AS h ON h.id = (' .
    'SELECT MAX(h2.id) FROM cpri_handoffs AS h2 WHERE h2.product_id = p.product_id) ' .
    'ORDER BY p.id DESC'
)->fetch_all(MYSQLI_ASSOC);

foreach ($productRows as &$productRow) {
    $currentCycle = (int) $productRow['rework_cycle'] + 1;
    $progress = ProductWorkflow::requiredTestProgress(
        $conn,
        (string) $productRow['product_id'],
        (int) $productRow['product_type_id'],
        $currentCycle
    );
    $productRow['current_cycle'] = $currentCycle;
    $productRow['required_tests'] = $progress['required'];
    $productRow['passed_tests'] = $progress['passed'];
}
unset($productRow);

require __DIR__ . '/views/product-workflow/index.php';
