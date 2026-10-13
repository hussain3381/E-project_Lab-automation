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
        $result = strtoupper(trim($result));
        if (!in_array($result, ['PASS', 'FAIL', 'PENDING'], true)) {
            throw new InvalidArgumentException('Choose PASS, FAIL, or PENDING as the test result.');
        }
        if ($testCycle < 1) {
            throw new InvalidArgumentException('A test must belong to a valid test cycle.');
        }

        // Even callers outside the standard form cannot change workflow on an archived or closed product.
        $availability = $connection->prepare(
            'SELECT is_archived, status, cpri_status, rework_cycle FROM products WHERE product_id = ? FOR UPDATE'
        );
        $availability->bind_param('s', $productId);
        $availability->execute();
        $productAvailability = $availability->get_result()->fetch_assoc();
        $availability->close();
        if ($productAvailability === null || (int) $productAvailability['is_archived'] === 1) {
            throw new DomainException('Archived or missing products cannot accept test results. Restore the product first.');
        }

        $currentCycle = (int) $productAvailability['rework_cycle'] + 1;
        if ($testCycle !== $currentCycle) {
            throw new DomainException('Only an assignment from the current test cycle can change the product workflow.');
        }
        if (in_array((string) $productAvailability['status'], ['Failed - Re-manufacturing', 'CPRI Ready', 'Handed to CPRI', 'CPRI Approved', 'Released to Market'], true)
            || in_array((string) $productAvailability['cpri_status'], ['Handed Off', 'Approved', 'Rejected', 'Released'], true)) {
            throw new DomainException('This product is not accepting test results in its current workflow stage.');
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
            'UPDATE products SET status = ?, rework_cycle = ?, cpri_status = ?, ' .
            'cpri_handoff_at = NULL, cpri_reference = NULL, cpri_handoff_by = NULL ' .
            'WHERE product_id = ?'
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
            'SELECT id AS product_record_id, status, is_archived FROM products WHERE product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (int) $product['is_archived'] === 1 || (string) $product['status'] !== 'Failed - Re-manufacturing') {
            throw new DomainException('Only an active product currently routed for re-manufacture can be released for retesting.');
        }

        self::validateActorAndNotes($changedBy, $notes);
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
        $reference = trim($reference);
        $notes = trim($notes);
        self::validateActorAndNotes($changedBy, $notes);
        if (strlen($reference) > 100) {
            throw new InvalidArgumentException('CPRI reference must be 100 characters or fewer.');
        }

        $statement = $connection->prepare(
            'SELECT p.id AS product_record_id, p.status, p.cpri_status, p.product_type_id, p.rework_cycle, p.is_archived ' .
            'FROM products AS p WHERE p.product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (int) $product['is_archived'] === 1
            || (string) $product['status'] !== 'CPRI Ready'
            || (string) $product['cpri_status'] !== 'Ready') {
            throw new DomainException('Only an active product marked CPRI Ready can be handed to CPRI.');
        }

        $cycle = (int) $product['rework_cycle'] + 1;
        if (!self::allRequiredTestsPassed($connection, $productId, (int) $product['product_type_id'], $cycle)) {
            throw new DomainException('The current product test cycle is not complete. Review required tests before handoff.');
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
     * Manually record the external CPRI outcome. A rejection follows the approved re-manufacture path.
     */
    public static function recordCpriResult(
        mysqli $connection,
        string $productId,
        string $result,
        string $notes,
        string $changedBy
    ): void {
        $result = strtoupper(trim($result));
        $notes = trim($notes);
        self::validateActorAndNotes($changedBy, $notes);
        if (!in_array($result, ['PASS', 'FAIL'], true)) {
            throw new InvalidArgumentException('Choose PASS (approved) or FAIL (rejected) for the CPRI result.');
        }

        $statement = $connection->prepare(
            'SELECT p.id AS product_record_id, p.status, p.cpri_status, p.rework_cycle, p.is_archived ' .
            'FROM products AS p WHERE p.product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (int) $product['is_archived'] === 1
            || (string) $product['status'] !== 'Handed to CPRI'
            || (string) $product['cpri_status'] !== 'Handed Off') {
            throw new DomainException('Record a CPRI result only after an active product has been handed off.');
        }

        $cycle = (int) $product['rework_cycle'] + 1;
        $handoffStatement = $connection->prepare(
            'SELECT id, cpri_result FROM cpri_handoffs WHERE product_id = ? AND test_cycle = ? LIMIT 1 FOR UPDATE'
        );
        $handoffStatement->bind_param('si', $productId, $cycle);
        $handoffStatement->execute();
        $handoff = $handoffStatement->get_result()->fetch_assoc();
        $handoffStatement->close();

        if (!$handoff) {
            throw new DomainException('The current cycle does not have a recorded CPRI handoff.');
        }
        if (strtoupper((string) $handoff['cpri_result']) !== 'PENDING') {
            throw new DomainException('A CPRI result has already been recorded for this handoff.');
        }

        $handoffId = (int) $handoff['id'];
        $saveResult = $connection->prepare(
            "UPDATE cpri_handoffs SET cpri_result = ?, cpri_result_notes = ?, cpri_result_by = ?, cpri_result_at = NOW() " .
            "WHERE id = ? AND cpri_result = 'PENDING'"
        );
        $saveResult->bind_param('sssi', $result, $notes, $changedBy, $handoffId);
        $saveResult->execute();
        $saved = $saveResult->affected_rows === 1;
        $saveResult->close();
        if (!$saved) {
            throw new DomainException('A CPRI result has already been recorded for this handoff.');
        }

        $oldStatus = (string) $product['status'];
        $newStatus = $result === 'PASS' ? 'CPRI Approved' : 'Failed - Re-manufacturing';
        $newCpriStatus = $result === 'PASS' ? 'Approved' : 'Rejected';
        $newReworkCycle = $result === 'FAIL' ? $cycle : (int) $product['rework_cycle'];
        $update = $connection->prepare(
            'UPDATE products SET status = ?, cpri_status = ?, rework_cycle = ? WHERE product_id = ?'
        );
        $update->bind_param('ssis', $newStatus, $newCpriStatus, $newReworkCycle, $productId);
        $update->execute();
        $update->close();

        $eventType = $result === 'PASS' ? 'CPRI_RESULT_PASS' : 'CPRI_RESULT_FAIL';
        $eventNotes = $result === 'PASS'
            ? 'Manually recorded CPRI result: PASS; product is eligible for market release.'
            : 'Manually recorded CPRI result: FAIL; product routed to re-manufacture before a new full test cycle.';
        if ($notes !== '') {
            $eventNotes .= ' ' . $notes;
        }
        self::writeWorkflowEvent(
            $connection,
            (int) $product['product_record_id'],
            $productId,
            $eventType,
            $oldStatus,
            $newStatus,
            $eventNotes,
            $changedBy
        );
    }

    /**
     * Record the final manual market-release approval after CPRI has passed.
     */
    public static function recordMarketRelease(
        mysqli $connection,
        string $productId,
        string $notes,
        string $changedBy
    ): void {
        $notes = trim($notes);
        self::validateActorAndNotes($changedBy, $notes);

        $statement = $connection->prepare(
            'SELECT p.id AS product_record_id, p.status, p.cpri_status, p.rework_cycle, p.is_archived ' .
            'FROM products AS p WHERE p.product_id = ? FOR UPDATE'
        );
        $statement->bind_param('s', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$product || (int) $product['is_archived'] === 1
            || (string) $product['status'] !== 'CPRI Approved'
            || (string) $product['cpri_status'] !== 'Approved') {
            throw new DomainException('Market release is allowed only after CPRI approval has been recorded.');
        }

        $cycle = (int) $product['rework_cycle'] + 1;
        $handoffStatement = $connection->prepare(
            'SELECT id, cpri_result, market_released_at FROM cpri_handoffs ' .
            'WHERE product_id = ? AND test_cycle = ? LIMIT 1 FOR UPDATE'
        );
        $handoffStatement->bind_param('si', $productId, $cycle);
        $handoffStatement->execute();
        $handoff = $handoffStatement->get_result()->fetch_assoc();
        $handoffStatement->close();

        if (!$handoff || strtoupper((string) ($handoff['cpri_result'] ?? '')) !== 'PASS') {
            throw new DomainException('A PASS result for the current CPRI handoff is required before market release.');
        }
        if (!empty($handoff['market_released_at'])) {
            throw new DomainException('Market release has already been recorded for this CPRI handoff.');
        }

        $handoffId = (int) $handoff['id'];
        $releaseNotes = $connection->prepare(
            'UPDATE cpri_handoffs SET market_released_by = ?, market_released_at = NOW(), market_release_notes = ? ' .
            'WHERE id = ? AND market_released_at IS NULL'
        );
        $releaseNotes->bind_param('ssi', $changedBy, $notes, $handoffId);
        $releaseNotes->execute();
        $saved = $releaseNotes->affected_rows === 1;
        $releaseNotes->close();
        if (!$saved) {
            throw new DomainException('Market release has already been recorded for this CPRI handoff.');
        }

        $newStatus = 'Released to Market';
        $newCpriStatus = 'Released';
        $update = $connection->prepare(
            'UPDATE products SET status = ?, cpri_status = ? WHERE product_id = ?'
        );
        $update->bind_param('sss', $newStatus, $newCpriStatus, $productId);
        $update->execute();
        $update->close();

        $eventNotes = $notes !== ''
            ? 'Manual market release recorded. ' . $notes
            : 'Manual market release recorded after CPRI approval.';
        self::writeWorkflowEvent(
            $connection,
            (int) $product['product_record_id'],
            $productId,
            'MARKET_RELEASE',
            (string) $product['status'],
            $newStatus,
            $eventNotes,
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

    private static function validateActorAndNotes(string $changedBy, string $notes): void
    {
        if (trim($changedBy) === '' || strlen($changedBy) > 120) {
            throw new InvalidArgumentException('The signed-in user name must be 1 to 120 characters.');
        }
        if (strlen($notes) > 5000) {
            throw new InvalidArgumentException('Workflow notes must be 5,000 characters or fewer.');
        }
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
