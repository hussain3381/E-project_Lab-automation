<?php
// Apply test outcomes to the product state machine and enforce the CPRI readiness gate.

declare(strict_types=1);

final class ProductWorkflow
{
    /**
     * Record the product state after a saved test result. Caller owns the transaction.
     * A failed test ends the current cycle and routes the product to re-manufacture.
     */
    public static function applyTestResult(
        mysqli $connection,
        string $productId,
        int $productTypeId,
        string $testId,
        int $testCycle,
        string $result,
        string $remarks,
        string $changedBy,
        string $oldStatus
    ): string {
        $result = strtoupper($result);
        if (!in_array($result, ['PASS', 'FAIL', 'PENDING'], true)) {
            throw new InvalidArgumentException('Choose PASS, FAIL, or PENDING as the test result.');
        }

        if ($result === 'FAIL') {
            $newStatus = 'Failed - Re-manufacturing';
            $newReworkCycle = $testCycle;
            $cpriStatus = 'Not Ready';
        } elseif ($result === 'PENDING') {
            $newStatus = 'Testing In Progress';
            $newReworkCycle = max(0, $testCycle - 1);
            $cpriStatus = 'Not Ready';
        } else {
            $newReworkCycle = max(0, $testCycle - 1);
            $newStatus = self::allRequiredTestsPassed($connection, $productId, $productTypeId, $testCycle)
                ? 'CPRI Ready'
                : 'Testing In Progress';
            $cpriStatus = $newStatus === 'CPRI Ready' ? 'Ready' : 'Not Ready';
        }

        $update = $connection->prepare(
            "UPDATE products SET status = ?, rework_cycle = ?, cpri_status = ?, " .
            "cpri_handoff_at = NULL, cpri_reference = NULL, cpri_handoff_by = NULL " .
            "WHERE product_id = ?"
        );
        $update->bind_param('siss', $newStatus, $newReworkCycle, $cpriStatus, $productId);
        $update->execute();
        $update->close();

        $historyRemarks = trim($remarks);
        if ($historyRemarks === '') {
            $historyRemarks = 'Test ' . $testId . ' result: ' . $result;
        }
        if ($result === 'FAIL') {
            $historyRemarks .= ' Product routed to re-manufacture; next cycle is ' . ($testCycle + 1) . '.';
        }

        self::writeStatusHistory($connection, $testId, $productId, $oldStatus, $newStatus, $historyRemarks, $changedBy);

        return $newStatus;
    }

    /**
     * Record that re-manufacturing is complete and the product may enter a new test cycle.
     */
    public static function markRemanufactured(mysqli $connection, string $productId, string $changedBy, string $notes = ''): void
    {
        $statement = $connection->prepare(
            'SELECT id AS product_record_id, status FROM products WHERE product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (string) $product['status'] !== 'Failed - Re-manufacturing') {
            throw new DomainException('Only a product currently routed for re-manufacture can be released for retesting.');
        }

        $newStatus = 'Ready for Retest';
        $update = $connection->prepare(
            "UPDATE products SET status = ?, cpri_status = 'Not Ready', " .
            'cpri_handoff_at = NULL, cpri_reference = NULL, cpri_handoff_by = NULL WHERE product_id = ?'
        );
        $update->bind_param('ss', $newStatus, $productId);
        $update->execute();
        $update->close();

        $historyNotes = trim($notes);
        if ($historyNotes === '') {
            $historyNotes = 'Re-manufacture completion recorded; product released for a new laboratory test cycle.';
        }
        self::writeWorkflowEvent(
            $connection,
            (int) $product['product_record_id'],
            $productId,
            'REMANUFACTURE_COMPLETE',
            (string) $product['status'],
            $newStatus,
            $historyNotes,
            $changedBy
        );
    }

    /**
     * Mark a product as handed to CPRI after the current required test set has passed.
     */
    public static function markCpriHandoff(
        mysqli $connection,
        string $productId,
        string $reference,
        string $notes,
        string $changedBy
    ): void {
        $statement = $connection->prepare(
            'SELECT p.id AS product_record_id, p.status, p.cpri_status, p.product_type_id, p.rework_cycle ' .
            'FROM products AS p WHERE p.product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (string) $product['cpri_status'] !== 'Ready') {
            throw new DomainException('Only a product with all required tests passed can be handed to CPRI.');
        }

        $cycle = (int) $product['rework_cycle'] + 1;
        if (!self::allRequiredTestsPassed($connection, $productId, (int) $product['product_type_id'], $cycle)) {
            throw new DomainException('The current product test cycle is not complete. Review required tests before handoff.');
        }

        $reference = trim($reference);
        if (strlen($reference) > 100) {
            throw new InvalidArgumentException('CPRI reference must be 100 characters or fewer.');
        }

        $handoff = $connection->prepare(
            'INSERT INTO cpri_handoffs (product_record_id, product_id, test_cycle, reference, notes, handed_off_by) ' .
            'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $productRecordId = (int) $product['product_record_id'];
        $handoff->bind_param('isisss', $productRecordId, $productId, $cycle, $reference, $notes, $changedBy);
        $handoff->execute();
        $handoff->close();

        $newStatus = 'Handed to CPRI';
        $newCpriStatus = 'Handed Off';
        $update = $connection->prepare(
            'UPDATE products SET status = ?, cpri_status = ?, cpri_handoff_at = NOW(), ' .
            'cpri_reference = ?, cpri_handoff_by = ? WHERE product_id = ?'
        );
        $update->bind_param('sssss', $newStatus, $newCpriStatus, $reference, $changedBy, $productId);
        $update->execute();
        $update->close();

        $statusNotes = trim($notes);
        if ($reference !== '') {
            $statusNotes = trim($statusNotes . ' CPRI reference: ' . $reference);
        }
        if ($statusNotes === '') {
            $statusNotes = 'Manual CPRI handoff recorded; no API integration is configured.';
        }
        self::writeWorkflowEvent(
            $connection,
            (int) $product['product_record_id'],
            $productId,
            'CPRI_HANDOFF',
            (string) $product['status'],
            $newStatus,
            $statusNotes,
            $changedBy
        );
    }

    /**
     * Return required-test progress for the current product-family plan and cycle.
     */
    public static function requiredTestProgress(mysqli $connection, string $productId, int $productTypeId, int $testCycle): array
    {
        if ($productTypeId < 1 || $testCycle < 1) {
            return ['required' => 0, 'passed' => 0];
        }

        $statement = $connection->prepare(
            'SELECT requirement.test_type_id, (' .
            'SELECT test.result FROM tests AS test ' .
            'WHERE test.product_id = ? AND test.test_type_id = requirement.test_type_id ' .
            'AND test.cycle_number = ? ORDER BY test.id DESC LIMIT 1' .
            ') AS latest_result ' .
            'FROM product_type_test_types AS requirement ' .
            'WHERE requirement.product_type_id = ? AND requirement.is_required = 1'
        );
        $statement->bind_param('sii', $productId, $testCycle, $productTypeId);
        $statement->execute();
        $result = $statement->get_result();
        $requiredCount = 0;
        $passedCount = 0;

        while ($row = $result->fetch_assoc()) {
            $requiredCount++;
            if (strtoupper((string) ($row['latest_result'] ?? '')) === 'PASS') {
                $passedCount++;
            }
        }
        $statement->close();

        return ['required' => $requiredCount, 'passed' => $passedCount];
    }

    /**
     * Return true only when at least one required test exists and its latest result in this cycle is PASS.
     */
    public static function allRequiredTestsPassed(mysqli $connection, string $productId, int $productTypeId, int $testCycle): bool
    {
        $progress = self::requiredTestProgress($connection, $productId, $productTypeId, $testCycle);

        return $progress['required'] > 0 && $progress['passed'] === $progress['required'];
    }

    /**
     * Save a small audit row in the existing product status history table.
     */
    private static function writeStatusHistory(
        mysqli $connection,
        string $testId,
        string $productId,
        string $oldStatus,
        string $newStatus,
        string $remarks,
        string $changedBy
    ): void {
        $history = $connection->prepare(
            'INSERT INTO test_history (test_id, product_id, old_status, new_status, remarks, changed_by) ' .
            'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $history->bind_param('ssssss', $testId, $productId, $oldStatus, $newStatus, $remarks, $changedBy);
        $history->execute();
        $history->close();
    }

    /**
     * Store re-manufacture and CPRI actions in a dedicated audit table rather than fabricating a Test ID.
     */
    private static function writeWorkflowEvent(
        mysqli $connection,
        int $productRecordId,
        string $productId,
        string $eventType,
        string $oldStatus,
        string $newStatus,
        string $notes,
        string $changedBy
    ): void {
        $event = $connection->prepare(
            'INSERT INTO product_workflow_events ' .
            '(product_record_id, product_id, event_type, old_status, new_status, notes, changed_by) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $event->bind_param('issssss', $productRecordId, $productId, $eventType, $oldStatus, $newStatus, $notes, $changedBy);
        $event->execute();
        $event->close();
    }
}
