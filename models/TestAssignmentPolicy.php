<?php
// Keep product/test/tester availability rules identical in the assignment form and its transaction.
declare(strict_types=1);

final class TestAssignmentPolicy
{
    private const ACTIVE_PRODUCT_STATES = ['Pending Testing', 'Testing In Progress', 'Ready for Retest'];
    private const CLOSED_CPRI_STATES = ['Handed Off', 'Approved', 'Rejected', 'Released'];

    /** List only product/test pairs that are valid next steps in the current cycle. */
    public static function availableProductTests(mysqli $connection): array
    {
        $sql = "SELECT p.id AS product_record_id, p.product_id, p.product_name, p.product_code, p.status AS product_status, "
            . "p.rework_cycle + 1 AS current_cycle, tt.id AS test_type_id, tt.test_code, tt.numeric_code, tt.test_name, tt.department_id, "
            . "COALESCE(d.department_name, tt.department, 'Unassigned') AS department, "
            . "ptt.sequence_no, ptt.is_required "
            . "FROM products AS p "
            . "INNER JOIN product_type_test_types AS ptt ON ptt.product_type_id = p.product_type_id "
            . "INNER JOIN test_types AS tt ON tt.id = ptt.test_type_id AND tt.is_active = 1 "
            . "LEFT JOIN departments AS d ON d.id = tt.department_id "
            . "WHERE p.is_archived = 0 "
            . "AND p.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest') "
            . "AND COALESCE(p.cpri_status, 'Not Ready') NOT IN ('Handed Off', 'Approved', 'Rejected', 'Released') "
            . "AND tt.numeric_code REGEXP '^[0-9]{3}$' "
            . "AND NOT EXISTS (SELECT 1 FROM tests AS open_test "
            . "INNER JOIN products AS open_product ON open_product.product_id = open_test.product_id "
            . "WHERE open_test.product_id = p.product_id "
            . "AND open_test.cycle_number = p.rework_cycle + 1 "
            . "AND open_product.is_archived = 0 "
            . "AND open_product.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest') "
            . "AND (UPPER(COALESCE(open_test.result, 'PENDING')) IN ('PENDING', '') "
            . "OR LOWER(COALESCE(open_test.status, '')) IN ('pending', 'in progress', 'testing in progress'))) "
            . "AND NOT EXISTS (SELECT 1 FROM tests AS prior_test "
            . "WHERE prior_test.product_id = p.product_id AND prior_test.cycle_number = p.rework_cycle + 1 "
            . "AND prior_test.test_type_id = tt.id) "
            . "ORDER BY p.id DESC, ptt.sequence_no ASC, tt.test_name ASC";

        $result = $connection->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $productId = (string) $row['product_id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'product_id' => $productId,
                    'product_name' => (string) $row['product_name'],
                    'product_code' => (string) $row['product_code'],
                    'product_status' => (string) $row['product_status'],
                    'current_cycle' => (int) $row['current_cycle'],
                    'tests' => [],
                ];
            }
            $products[$productId]['tests'][] = [
                'id' => (int) $row['test_type_id'],
                'code' => (string) $row['test_code'],
                'numeric_code' => (string) $row['numeric_code'],
                'name' => (string) $row['test_name'],
                'department' => (string) $row['department'],
                'department_id' => $row['department_id'] === null ? null : (int) $row['department_id'],
                'sequence' => (int) $row['sequence_no'],
                'required' => (int) $row['is_required'] === 1,
            ];
        }
        $result->free();

        return array_values($products);
    }

    /** Return available and busy testers so the UI can explain why a person is disabled. */
    public static function testerDirectory(mysqli $connection): array
    {
        $sql = "SELECT tester.id, tester.name, tester.department, tester.designation, tester.is_active, tester.user_id, "
            . "linked_user.is_active AS user_is_active, "
            . "(SELECT active_test.test_id FROM tests AS active_test "
            . "INNER JOIN products AS active_product ON active_product.product_id = active_test.product_id "
            . "WHERE active_product.is_archived = 0 "
            . "AND active_product.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest') "
            . "AND active_test.cycle_number = active_product.rework_cycle + 1 "
            . "AND (UPPER(COALESCE(active_test.result, 'PENDING')) IN ('PENDING', '') "
            . "OR LOWER(COALESCE(active_test.status, '')) IN ('pending', 'in progress', 'testing in progress')) "
            . "AND (active_test.tester_id = tester.id OR EXISTS (SELECT 1 FROM test_participants AS participant "
            . "WHERE participant.test_record_id = active_test.id AND participant.tester_id = tester.id)) "
            . "ORDER BY active_test.id ASC LIMIT 1) AS busy_test_id "
            . "FROM testers AS tester LEFT JOIN users AS linked_user ON linked_user.id = tester.user_id "
            . "WHERE tester.is_active = 1 ORDER BY tester.name ASC, tester.id ASC";

        $result = $connection->query($sql);
        $testers = [];
        while ($row = $result->fetch_assoc()) {
            $profileActive = (int) $row['is_active'] === 1;
            $accountActive = $row['user_id'] === null || (int) ($row['user_is_active'] ?? 0) === 1;
            $busyTestId = trim((string) ($row['busy_test_id'] ?? ''));
            $testers[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'department' => (string) ($row['department'] ?? ''),
                'designation' => (string) ($row['designation'] ?? ''),
                'available' => $profileActive && $accountActive && $busyTestId === '',
                'busy_test_id' => $busyTestId,
                'unavailable_reason' => !$accountActive ? 'Account is inactive' : ($busyTestId !== '' ? 'Busy on ' . $busyTestId : ''),
            ];
        }
        $result->free();

        return $testers;
    }

    /** Lock the product row and reject stale, closed, archived, or already-open assignments. */
    public static function lockAssignableProduct(mysqli $connection, string $productId, int $testTypeId): array
    {
        $statement = $connection->prepare(
            'SELECT id, product_id, product_type_id, status, cpri_status, rework_cycle, is_archived '
            . 'FROM products WHERE product_id = ? LIMIT 1 FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        if ($product === null || (int) $product['is_archived'] === 1) {
            throw new DomainException('That product is unavailable or archived. Refresh the list and choose an active product.');
        }
        if (!in_array((string) $product['status'], self::ACTIVE_PRODUCT_STATES, true)
            || in_array((string) $product['cpri_status'], self::CLOSED_CPRI_STATES, true)) {
            throw new DomainException('This product is not accepting a new test in its current workflow stage.');
        }

        $cycle = (int) $product['rework_cycle'] + 1;
        $open = $connection->prepare(
            "SELECT test_id FROM tests WHERE product_id = ? AND cycle_number = ? "
            . "AND (UPPER(COALESCE(result, 'PENDING')) IN ('PENDING', '') "
            . "OR LOWER(COALESCE(status, '')) IN ('pending', 'in progress', 'testing in progress')) "
            . 'ORDER BY id ASC LIMIT 1'
        );
        $open->bind_param('si', $productId, $cycle);
        $open->execute();
        $openTest = $open->get_result()->fetch_assoc() ?: null;
        $open->close();
        if ($openTest !== null) {
            throw new DomainException('This product already has an open test (' . (string) $openTest['test_id'] . '). Complete it before assigning the next test.');
        }

        $mapping = $connection->prepare(
            'SELECT ptt.sequence_no, ptt.is_required, tt.numeric_code, tt.department_id, tt.is_active '
            . 'FROM product_type_test_types AS ptt INNER JOIN test_types AS tt ON tt.id = ptt.test_type_id '
            . 'WHERE ptt.product_type_id = ? AND ptt.test_type_id = ? LIMIT 1 FOR UPDATE'
        );
        $productTypeId = (int) $product['product_type_id'];
        $mapping->bind_param('ii', $productTypeId, $testTypeId);
        $mapping->execute();
        $testPlanRow = $mapping->get_result()->fetch_assoc() ?: null;
        $mapping->close();
        if ($testPlanRow === null || (int) $testPlanRow['is_active'] !== 1
            || !preg_match('/^\d{3}$/', (string) $testPlanRow['numeric_code'])) {
            throw new DomainException('Choose an active test from this product family’s test plan.');
        }

        $duplicate = $connection->prepare(
            'SELECT test_id FROM tests WHERE product_id = ? AND cycle_number = ? AND test_type_id = ? LIMIT 1'
        );
        $duplicate->bind_param('sii', $productId, $cycle, $testTypeId);
        $duplicate->execute();
        $existing = $duplicate->get_result()->fetch_assoc() ?: null;
        $duplicate->close();
        if ($existing !== null) {
            throw new DomainException('That test type is already recorded for the current cycle. Assign another uncompleted test from the family plan.');
        }

        $product['current_cycle'] = $cycle;
        $product['test_plan_row'] = $testPlanRow;

        return $product;
    }

    /** Lock tester rows in a stable order so one person cannot receive two simultaneous open assignments. */
    public static function lockAvailableTesters(mysqli $connection, array $testerIds): array
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $testerIds), static fn (int $id): bool => $id > 0)));
        if ($uniqueIds === []) {
            throw new DomainException('Choose at least one available tester.');
        }
        $lockIds = $uniqueIds;
        sort($lockIds, SORT_NUMERIC);
        $locked = [];

        foreach ($lockIds as $testerId) {
            $statement = $connection->prepare(
                'SELECT tester.id, tester.name, tester.is_active, tester.user_id, linked_user.is_active AS user_is_active '
                . 'FROM testers AS tester LEFT JOIN users AS linked_user ON linked_user.id = tester.user_id '
                . 'WHERE tester.id = ? LIMIT 1 FOR UPDATE'
            );
            $statement->bind_param('i', $testerId);
            $statement->execute();
            $tester = $statement->get_result()->fetch_assoc() ?: null;
            $statement->close();
            if ($tester === null || (int) $tester['is_active'] !== 1
                || ($tester['user_id'] !== null && (int) ($tester['user_is_active'] ?? 0) !== 1)) {
                throw new DomainException('One or more selected tester profiles or accounts are inactive. Refresh the tester list.');
            }

            $busy = $connection->prepare(
                "SELECT active_test.test_id FROM tests AS active_test "
                . "INNER JOIN products AS active_product ON active_product.product_id = active_test.product_id "
                . "WHERE active_product.is_archived = 0 "
                . "AND active_product.status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest') "
                . 'AND active_test.cycle_number = active_product.rework_cycle + 1 '
                . "AND (UPPER(COALESCE(active_test.result, 'PENDING')) IN ('PENDING', '') "
                . "OR LOWER(COALESCE(active_test.status, '')) IN ('pending', 'in progress', 'testing in progress')) "
                . 'AND (active_test.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS participant '
                . 'WHERE participant.test_record_id = active_test.id AND participant.tester_id = ?)) '
                . 'ORDER BY active_test.id ASC LIMIT 1'
            );
            $busy->bind_param('ii', $testerId, $testerId);
            $busy->execute();
            $busyRow = $busy->get_result()->fetch_assoc() ?: null;
            $busy->close();
            if ($busyRow !== null) {
                throw new DomainException((string) $tester['name'] . ' is already working on ' . (string) $busyRow['test_id'] . '. Complete that test before assigning another.');
            }

            $locked[$testerId] = ['id' => $testerId, 'name' => (string) $tester['name']];
        }

        return array_map(static fn (int $id): array => $locked[$id], $uniqueIds);
    }
}
