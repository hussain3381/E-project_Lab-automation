<?php
// Replace one product family's required/optional test mapping in one transaction.

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ProductCatalog.php';

function product_test_plan_save(mysqli $connection): array
{
    $state = ['message' => '', 'error' => ''];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return $state;
    }

    $productTypeId = (int) ($_POST['product_type_id'] ?? 0);
    $testTypeIds = $_POST['test_type_ids'] ?? [];
    $requiredIds = $_POST['required_test_type_ids'] ?? [];
    if (!is_array($testTypeIds) || !is_array($requiredIds)) {
        $state['error'] = 'Choose valid test plan options.';
        return $state;
    }

    $testTypeIds = array_values(array_unique(array_filter(array_map('intval', $testTypeIds), static fn (int $id): bool => $id > 0)));
    $requiredIds = array_values(array_unique(array_filter(array_map('intval', $requiredIds), static fn (int $id): bool => $id > 0)));

    if (ProductCatalog::findActiveType($connection, $productTypeId) === null) {
        $state['error'] = 'Select an active product family.';
        return $state;
    }

    if (array_diff($requiredIds, $testTypeIds) !== []) {
        $state['error'] = 'A test cannot be marked required unless it is included in the family plan.';
        return $state;
    }

    $activeTests = $connection->query('SELECT id FROM test_types WHERE is_active = 1')->fetch_all(MYSQLI_ASSOC);
    $activeIds = array_map(static fn (array $row): int => (int) $row['id'], $activeTests);
    if (array_diff($testTypeIds, $activeIds) !== []) {
        $state['error'] = 'The plan contains an inactive or unknown test type. Reload and try again.';
        return $state;
    }

    try {
        $connection->begin_transaction();
        $delete = $connection->prepare('DELETE FROM product_type_test_types WHERE product_type_id = ?');
        $delete->bind_param('i', $productTypeId);
        $delete->execute();
        $delete->close();

        $insert = $connection->prepare(
            'INSERT INTO product_type_test_types (product_type_id, test_type_id, sequence_no, is_required) VALUES (?, ?, ?, ?)'
        );
        foreach ($testTypeIds as $index => $testTypeId) {
            $sequence = $index + 1;
            $isRequired = in_array($testTypeId, $requiredIds, true) ? 1 : 0;
            $insert->bind_param('iiii', $productTypeId, $testTypeId, $sequence, $isRequired);
            $insert->execute();
        }
        $insert->close();
        $connection->commit();
        $state['message'] = 'Product-family test plan saved.';
    } catch (Throwable $exception) {
        $connection->rollback();
        error_log('Lab Automation test-plan write failed: ' . $exception->getMessage());
        $state['error'] = 'The plan could not be saved. Check the selected tests and try again.';
    }

    return $state;
}
