<?php
// Let Lab Managers revise pending assignment details; keep outcome correction Administrator-only.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/ProductWorkflow.php';

$currentRole = (string) ($_SESSION['role'] ?? '');
$isAdministrator = $currentRole === 'Administrator';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($recordId < 1) {
    http_response_code(400);
    exit('Invalid test record.');
}

$error = '';
$loadRecord = static function (mysqli $connection, int $id, bool $forUpdate = false): ?array {
    $sql = 'SELECT t.id, t.test_id, t.product_id, t.test_type_id, t.tester_id, t.testing_date, '
        . 't.criteria, t.expected_output, t.actual_output, t.result, t.status, t.remarks, t.cycle_number, '
        . 'tt.test_name, p.product_name, p.product_type_id, p.status AS product_status, p.cpri_status, p.rework_cycle, p.is_archived '
        . 'FROM tests AS t INNER JOIN products AS p ON p.product_id = t.product_id '
        . 'LEFT JOIN test_types AS tt ON tt.id = t.test_type_id WHERE t.id = ? LIMIT 1'
        . ($forUpdate ? ' FOR UPDATE' : '');
    $statement = $connection->prepare($sql);
    $statement->bind_param('i', $id);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();
    return $record;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $testingDate = trim((string) ($_POST['testing_date'] ?? ''));
    $criteria = trim((string) ($_POST['criteria'] ?? ''));
    $expectedOutput = trim((string) ($_POST['expected_output'] ?? ''));
    $actualOutput = $isAdministrator ? trim((string) ($_POST['actual_output'] ?? '')) : '';
    $resultValue = $isAdministrator ? strtoupper(trim((string) ($_POST['result'] ?? ''))) : 'PENDING';
    $remarks = trim((string) ($_POST['remarks'] ?? ''));
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $testingDate);
    $validDate = $parsedDate !== false && $parsedDate->format('Y-m-d') === $testingDate;

    if (!$validDate) {
        $error = 'Enter a valid testing date.';
    } elseif ($criteria === '' || strlen($criteria) > 60000) {
        $error = 'Test criteria is required and must be 60,000 characters or fewer.';
    } elseif ($expectedOutput === '' || strlen($expectedOutput) > 60000) {
        $error = 'Expected output is required and must be 60,000 characters or fewer.';
    } elseif (strlen($actualOutput) > 60000 || strlen($remarks) > 60000) {
        $error = 'Actual output and remarks must be 60,000 characters or fewer.';
    } elseif (!in_array($resultValue, ['PASS', 'FAIL', 'PENDING'], true)) {
        $error = 'Choose PASS, FAIL, or PENDING.';
    } elseif ($resultValue !== 'PENDING' && $actualOutput === '') {
        $error = 'Actual output is required when the result is PASS or FAIL.';
    } else {
        try {
            $conn->begin_transaction();
            $before = $loadRecord($conn, (int) $recordId, true);
            if ($before === null) {
                throw new DomainException('The selected test record no longer exists.');
            }

            $oldResult = strtoupper((string) $before['result']);
            if (!$isAdministrator) {
                $currentCycle = (int) $before['rework_cycle'] + 1;
                $assignmentCycle = max(1, (int) ($before['cycle_number'] ?? $currentCycle));
                $editableStates = ['Pending Testing', 'Testing In Progress', 'Ready for Retest'];
                $closedCpriStates = ['Handed Off', 'Approved', 'Rejected', 'Released'];
                if ($oldResult !== 'PENDING' || $assignmentCycle !== $currentCycle
                    || (int) $before['is_archived'] === 1
                    || !in_array((string) $before['product_status'], $editableStates, true)
                    || in_array((string) $before['cpri_status'], $closedCpriStates, true)) {
                    throw new DomainException('Lab Managers can revise instructions only while the assignment is pending in the active product cycle. Completed-result corrections are Administrator-only.');
                }
                $resultValue = $oldResult;
                $actualOutput = (string) ($before['actual_output'] ?? '');
            }
            $resultChanged = $oldResult !== $resultValue;
            if ($resultChanged && ((int) $before['is_archived'] === 1
                || in_array((string) $before['cpri_status'], ['Handed Off', 'Approved', 'Rejected', 'Released'], true)
                || in_array((string) $before['product_status'], ['Handed to CPRI', 'CPRI Approved', 'Released to Market'], true))) {
                throw new DomainException('This product is archived, handed to CPRI, or approved/released. Its test outcome is locked to protect workflow traceability.');
            }
            if ($resultChanged && (string) $before['product_status'] === 'Failed - Re-manufacturing') {
                throw new DomainException('This product is already in the re-manufacture workflow. Correct the result only after the workflow is reviewed by an Administrator.');
            }

            $newTestStatus = !$isAdministrator
                ? (string) $before['status']
                : ($resultValue === 'PENDING' ? 'Pending' : 'Completed');
            $update = $conn->prepare(
                'UPDATE tests SET testing_date = ?, criteria = ?, expected_output = ?, actual_output = ?, result = ?, status = ?, remarks = ? WHERE id = ?'
            );
            $update->bind_param('sssssssi', $testingDate, $criteria, $expectedOutput, $actualOutput, $resultValue, $newTestStatus, $remarks, $recordId);
            $update->execute();
            $update->close();

            $workflowUpdated = false;
            $oldProductStatus = (string) $before['product_status'];
            $newProductStatus = $oldProductStatus;
            $testCycle = (int) ($before['cycle_number'] ?? 1);
            $currentCycle = (int) $before['rework_cycle'] + 1;
            if ($resultChanged && $testCycle === $currentCycle) {
                $productId = (string) $before['product_id'];
                $failureCheck = $conn->prepare("SELECT COUNT(*) AS total FROM tests WHERE product_id = ? AND cycle_number = ? AND UPPER(result) = 'FAIL'");
                $failureCheck->bind_param('si', $productId, $testCycle);
                $failureCheck->execute();
                $cycleHasFailure = (int) ($failureCheck->get_result()->fetch_assoc()['total'] ?? 0) > 0;
                $failureCheck->close();

                if ($cycleHasFailure) {
                    $newProductStatus = 'Failed - Re-manufacturing';
                    $newCpriStatus = 'Not Ready';
                    $newReworkCycle = $testCycle;
                } elseif (ProductWorkflow::allRequiredTestsPassed($conn, $productId, (int) $before['product_type_id'], $testCycle)) {
                    $newProductStatus = 'CPRI Ready';
                    $newCpriStatus = 'Ready';
                    $newReworkCycle = (int) $before['rework_cycle'];
                } else {
                    $newProductStatus = 'Testing In Progress';
                    $newCpriStatus = 'Not Ready';
                    $newReworkCycle = (int) $before['rework_cycle'];
                }

                $productUpdate = $conn->prepare(
                    'UPDATE products SET status = ?, cpri_status = ?, rework_cycle = ?, ' .
                    'cpri_handoff_at = NULL, cpri_reference = NULL, cpri_handoff_by = NULL WHERE product_id = ?'
                );
                $productUpdate->bind_param('ssis', $newProductStatus, $newCpriStatus, $newReworkCycle, $productId);
                $productUpdate->execute();
                $productUpdate->close();
                $workflowUpdated = true;

                if ($newProductStatus !== $oldProductStatus) {
                    $changedBy = (string) ($_SESSION['name'] ?? 'Administrator');
                    $historyNote = 'Administrator corrected Test ' . (string) $before['test_id'] . ' result; product state recalculated for cycle ' . $testCycle . '.';
                    $history = $conn->prepare('INSERT INTO test_history (test_id, product_id, old_status, new_status, remarks, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $history->bind_param('ssssss', $before['test_id'], $productId, $oldProductStatus, $newProductStatus, $historyNote, $changedBy);
                    $history->execute();
                    $history->close();
                }
            }

            audit_write($conn, 'test_record_corrected', 'Test', (string) $before['test_id'], (string) $currentRole . ' corrected a test record; original values were preserved in audit metadata.', [
                'record_id' => (int) $recordId,
                'product_id' => (string) $before['product_id'],
                'test_cycle' => $testCycle,
                'workflow_recalculated' => $workflowUpdated,
                'product_status_before' => $oldProductStatus,
                'product_status_after' => $newProductStatus,
                'before' => [
                    'testing_date' => (string) $before['testing_date'],
                    'criteria' => (string) $before['criteria'],
                    'expected_output' => (string) $before['expected_output'],
                    'actual_output' => (string) ($before['actual_output'] ?? ''),
                    'result' => $oldResult,
                    'status' => (string) $before['status'],
                    'remarks' => (string) ($before['remarks'] ?? ''),
                ],
                'after' => [
                    'testing_date' => $testingDate,
                    'criteria' => $criteria,
                    'expected_output' => $expectedOutput,
                    'actual_output' => $actualOutput,
                    'result' => $resultValue,
                    'status' => $newTestStatus,
                    'remarks' => $remarks,
                ],
            ]);

            $conn->commit();
            $_SESSION['test_edit_flash'] = $isAdministrator
                ? 'Test record corrected. The stable Test ID and Product ID were preserved.'
                : 'Test assignment details saved. The pending result and Tester assignments were preserved.';
            header('Location: test-details.php?id=' . (int) $recordId, true, 303);
            exit;
        } catch (DomainException | InvalidArgumentException $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve validation message. */ }
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
            error_log('Lab Automation test detail update failed: ' . $exception->getMessage());
            $error = 'Test record could not be corrected. Please retry.';
        }
    }
}

$record = $loadRecord($conn, (int) $recordId);
if ($record === null) {
    http_response_code(404);
    exit('Test record not found.');
}

$managerEditableStates = ['Pending Testing', 'Testing In Progress', 'Ready for Retest'];
$managerOpenStatuses = ['pending', 'in progress', 'testing in progress'];
$managerCanEdit = $isAdministrator || (
    $currentRole === 'Lab Manager'
    && strtoupper((string) $record['result']) === 'PENDING'
    && in_array(strtolower(trim((string) $record['status'])), $managerOpenStatuses, true)
    && (int) $record['cycle_number'] === (int) $record['rework_cycle'] + 1
    && (int) $record['is_archived'] !== 1
    && in_array((string) $record['product_status'], $managerEditableStates, true)
    && !in_array((string) $record['cpri_status'], ['Handed Off', 'Approved', 'Rejected', 'Released'], true)
);
if ($currentRole === 'Lab Manager' && !$managerCanEdit && $error === '') {
    $error = 'This assignment is no longer pending in the active product cycle. Completed-result corrections are Administrator-only.';
}

$pageTitle = $isAdministrator ? 'Correct test record' : 'Edit test details';
$pageEyebrow = $isAdministrator ? 'ADMIN / RECORD CONTROL' : 'ASSIGNMENT INSTRUCTIONS';
$pageDescription = $isAdministrator
    ? 'Correct measurement and result fields while preserving the stable Test ID, assignments, and an audit copy of the previous values.'
    : 'Revise the date, criteria, expected output, and notes on an open pending assignment. Test outcome and assigned testers stay protected.';
$pageActionHtml = '<a class="button button-quiet" href="test-details.php?id=' . (int) $recordId . '"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to test record</a>';
require __DIR__ . '/views/layouts/app_start.php';
?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<section class="panel edit-test-summary" data-reveal>
    <div><p class="eyebrow">STABLE RECORD ID</p><h2><?php echo htmlspecialchars((string) $record['test_id'], ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars((string) $record['product_name'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) $record['product_id'], ENT_QUOTES, 'UTF-8'); ?></p></div>
    <div class="edit-test-locks"><span><strong>Test:</strong> <?php echo htmlspecialchars((string) $record['test_name'], ENT_QUOTES, 'UTF-8'); ?></span><span><strong>Cycle:</strong> <?php echo (int) $record['cycle_number']; ?></span><span><strong>Product state:</strong> <?php echo htmlspecialchars((string) $record['product_status'], ENT_QUOTES, 'UTF-8'); ?></span></div>
</section>

<div class="alert edit-test-history-note" role="note">
    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
    <span>
        Test ID, product, test type, cycle, and Tester assignments stay locked. Changes are written to the audit log.
        <?php if ($isAdministrator): ?> Administrators can correct outcomes where the product workflow allows it.
        <?php else: ?> Lab Managers can edit instructions only while this assignment remains pending in the active cycle; completed results are Administrator-only.
        <?php endif; ?>
    </span>
</div>

<?php if (!$managerCanEdit): ?>
    <section class="panel" data-reveal>
        <div class="empty-state">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <h2>Assignment details are locked</h2>
            <p>This record is no longer an open pending assignment in the current product cycle. Contact an Administrator if a completed result needs correction.</p>
            <a class="button button-secondary" href="test-details.php?id=<?php echo (int) $recordId; ?>">Return to test record</a>
        </div>
    </section>
<?php else: ?>
    <section class="panel" data-reveal>
        <form class="edit-test-form" method="post" action="edit-test.php?id=<?php echo (int) $recordId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-field"><label for="edit-testing-date">Testing date</label><input id="edit-testing-date" name="testing_date" type="date" value="<?php echo htmlspecialchars((string) ($_POST['testing_date'] ?? $record['testing_date']), ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <?php if ($isAdministrator): ?>
                <div class="form-field"><label for="edit-result">Result</label><select id="edit-result" name="result" required><?php foreach (['PENDING', 'PASS', 'FAIL'] as $resultOption): ?><option value="<?php echo $resultOption; ?>"<?php echo strtoupper((string) ($_POST['result'] ?? $record['result'])) === $resultOption ? ' selected' : ''; ?>><?php echo $resultOption; ?></option><?php endforeach; ?></select></div>
            <?php else: ?>
                <div class="form-field"><label>Assignment status</label><input type="text" value="Pending — result is entered by the assigned Tester" readonly></div>
            <?php endif; ?>
            <div class="form-field form-field-full"><label for="edit-criteria">Test criteria</label><textarea id="edit-criteria" name="criteria" rows="4" maxlength="60000" required><?php echo htmlspecialchars((string) ($_POST['criteria'] ?? $record['criteria']), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <div class="form-field form-field-full"><label for="edit-expected">Expected output</label><textarea id="edit-expected" name="expected_output" rows="4" maxlength="60000" required><?php echo htmlspecialchars((string) ($_POST['expected_output'] ?? $record['expected_output']), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <?php if ($isAdministrator): ?>
                <div class="form-field form-field-full"><label for="edit-actual">Actual output / measurements</label><textarea id="edit-actual" name="actual_output" rows="5" maxlength="60000"><?php echo htmlspecialchars((string) ($_POST['actual_output'] ?? $record['actual_output'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <?php endif; ?>
            <div class="form-field form-field-full"><label for="edit-remarks">Detailed remarks</label><textarea id="edit-remarks" name="remarks" rows="4" maxlength="60000"><?php echo htmlspecialchars((string) ($_POST['remarks'] ?? $record['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <div class="form-actions form-field-full"><button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?php echo $isAdministrator ? 'Save correction' : 'Save test details'; ?></button><a class="button button-secondary" href="test-details.php?id=<?php echo (int) $recordId; ?>">Cancel</a></div>
        </form>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
