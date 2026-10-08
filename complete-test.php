<?php
// Capture the outcome for an existing, assigned pending test without creating a duplicate record.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
include __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/Tester.php';
require_once __DIR__ . '/models/ProductWorkflow.php';

$testerProfile = Tester::findByUserId($conn, (int) ($_SESSION['user_id'] ?? 0));
if ($testerProfile === null) {
    RoleMiddleware::handle([]);
}
$testerId = (int) $testerProfile['id'];
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recordId || $recordId < 1) {
    http_response_code(400);
    exit('Invalid test assignment.');
}

$isOpenAssignment = static function (?array $assignedRecord): bool {
    if ($assignedRecord === null || strtoupper((string) ($assignedRecord['result'] ?? '')) !== 'PENDING') {
        return false;
    }

    return in_array(strtolower(trim((string) ($assignedRecord['status'] ?? ''))), ['pending', 'in progress', 'testing in progress'], true);
};

$loadAssignedRecord = static function (mysqli $connection, int $id, int $assignedTesterId, bool $forUpdate = false): ?array {
    $sql = 'SELECT t.id, t.test_id, t.product_id, t.test_type_id, t.testing_date, t.criteria, '
        . 't.expected_output, t.actual_output, t.result, t.status, t.remarks, t.cycle_number, '
        . 'p.product_name, p.product_type_id, p.status AS product_status, p.rework_cycle, '
        . 'tt.test_name FROM tests AS t '
        . 'INNER JOIN products AS p ON p.product_id = t.product_id '
        . 'INNER JOIN test_types AS tt ON tt.id = t.test_type_id '
        . 'WHERE t.id = ? AND (t.tester_id = ? OR EXISTS ('
        . 'SELECT 1 FROM test_participants AS tp WHERE tp.test_record_id = t.id AND tp.tester_id = ?)) '
        . 'LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
    $statement = $connection->prepare($sql);
    $statement->bind_param('iii', $id, $assignedTesterId, $assignedTesterId);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();

    return $record;
};

$error = '';
$record = $loadAssignedRecord($conn, (int) $recordId, $testerId);
if ($record === null) {
    http_response_code(404);
    exit('Assigned test record not found.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $actualOutput = trim((string) ($_POST['actual_output'] ?? ''));
    $resultValue = strtoupper(trim((string) ($_POST['result'] ?? '')));
    $testingDate = trim((string) ($_POST['testing_date'] ?? ''));
    $remarks = trim((string) ($_POST['remarks'] ?? ''));
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $testingDate);
    $validDate = $parsedDate !== false && $parsedDate->format('Y-m-d') === $testingDate;

    if (!in_array($resultValue, ['PASS', 'FAIL'], true)) {
        $error = 'Choose PASS or FAIL for the completed test.';
    } elseif ($actualOutput === '' || strlen($actualOutput) > 60000) {
        $error = 'Enter the actual output (up to 60,000 characters).';
    } elseif (!$validDate) {
        $error = 'Enter a valid testing date.';
    } elseif (strlen($remarks) > 60000) {
        $error = 'Remarks must be 60,000 characters or fewer.';
    } else {
        try {
            $conn->begin_transaction();
            $lockedRecord = $loadAssignedRecord($conn, (int) $recordId, $testerId, true);
            if ($lockedRecord === null) {
                throw new DomainException('This test is not assigned to your tester profile.');
            }
            if (!$isOpenAssignment($lockedRecord)) {
                throw new DomainException('This assigned test is no longer open for a result. Reload the test history.');
            }

            $productLock = $conn->prepare(
                'SELECT product_type_id, status, rework_cycle FROM products WHERE product_id = ? FOR UPDATE'
            );
            $productId = (string) $lockedRecord['product_id'];
            $productLock->bind_param('s', $productId);
            $productLock->execute();
            $lockedProduct = $productLock->get_result()->fetch_assoc();
            $productLock->close();
            if (!$lockedProduct) {
                throw new DomainException('The assigned product record is no longer available.');
            }
            if (in_array((string) $lockedProduct['status'], ['Failed - Re-manufacturing', 'CPRI Ready', 'Handed to CPRI'], true)) {
                throw new DomainException('The product workflow no longer accepts this test. Ask a Lab Manager to review the assignment.');
            }

            $testCycle = max(1, (int) ($lockedRecord['cycle_number'] ?? ((int) $lockedProduct['rework_cycle'] + 1)));
            $complete = $conn->prepare(
                "UPDATE tests SET actual_output = ?, result = ?, status = 'Completed', testing_date = ?, remarks = ? "
                . "WHERE id = ? AND UPPER(result) = 'PENDING'"
            );
            $complete->bind_param('ssssi', $actualOutput, $resultValue, $testingDate, $remarks, $recordId);
            $complete->execute();
            if ($complete->affected_rows !== 1) {
                $complete->close();
                throw new DomainException('This assigned test was already completed. Reload the test history.');
            }
            $complete->close();

            $changedBy = (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'Tester');
            ProductWorkflow::applyTestResult(
                $conn,
                $productId,
                (int) $lockedProduct['product_type_id'],
                (string) $lockedRecord['test_id'],
                $testCycle,
                $resultValue,
                $remarks,
                $changedBy,
                (string) $lockedProduct['status']
            );

            $conn->commit();
            header('Location: test-details.php?id=' . (int) $recordId . '&saved=1', true, 303);
            exit;
        } catch (Throwable $exception) {
            try { $conn->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
            error_log('Assigned test completion failed: ' . $exception->getMessage());
            $error = $exception instanceof DomainException
                ? $exception->getMessage()
                : 'Could not save the assigned test result. Reload the record and try again.';
            $record = $loadAssignedRecord($conn, (int) $recordId, $testerId) ?? $record;
        }
    }
}

$showCompletionForm = $isOpenAssignment($record);
if (!$showCompletionForm) {
    $error = $error !== '' ? $error : 'This assigned test is no longer open for a result.';
}

$pageTitle = 'Complete assigned test';
$pageEyebrow = 'TESTER WORKSPACE';
$pageDescription = 'Enter measured output and a final result for this test assignment. The result is added to the product history.';
$pageActionHtml = '<a class="button button-quiet" href="test-details.php?id=' . (int) $recordId . '"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to details</a>';
$pageStylesheet = 'assets/css/pages/complete-test.css';
require __DIR__ . '/views/layouts/app_start.php';
?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<section class="panel assignment-summary" data-reveal>
    <div>
        <p class="eyebrow">ASSIGNED TEST</p>
        <h2><?php echo htmlspecialchars((string) $record['test_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars((string) $record['product_name'], ENT_QUOTES, 'UTF-8'); ?> · Product ID <strong><?php echo htmlspecialchars((string) $record['product_id'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    </div>
    <div class="assignment-id"><span>Test ID</span><strong><?php echo htmlspecialchars((string) $record['test_id'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
</section>

<section class="panel assignment-plan" data-reveal>
    <div class="plan-item"><span>Test criteria</span><p><?php echo nl2br(htmlspecialchars((string) $record['criteria'], ENT_QUOTES, 'UTF-8')); ?></p></div>
    <div class="plan-item"><span>Expected output</span><p><?php echo nl2br(htmlspecialchars((string) $record['expected_output'], ENT_QUOTES, 'UTF-8')); ?></p></div>
</section>

<?php if ($showCompletionForm): ?>
<section class="panel" data-reveal>
    <form method="post" action="complete-test.php?id=<?php echo (int) $recordId; ?>" class="completion-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="record_id" value="<?php echo (int) $recordId; ?>">
        <div class="form-field">
            <label for="testing-date">Date tested</label>
            <input id="testing-date" name="testing_date" type="date" value="<?php echo htmlspecialchars((string) ($_POST['testing_date'] ?? $record['testing_date']), ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-field">
            <label for="result">Final result</label>
            <select id="result" name="result" required>
                <option value="">Select result</option>
                <?php foreach (['PASS', 'FAIL'] as $resultOption): ?>
                    <option value="<?php echo $resultOption; ?>"<?php echo strtoupper((string) ($_POST['result'] ?? '')) === $resultOption ? ' selected' : ''; ?>><?php echo $resultOption; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field form-field-full">
            <label for="actual-output">Actual output / measurements</label>
            <textarea id="actual-output" name="actual_output" rows="5" maxlength="60000" required><?php echo htmlspecialchars((string) ($_POST['actual_output'] ?? $record['actual_output'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="form-field form-field-full">
            <label for="remarks">Detailed remarks</label>
            <textarea id="remarks" name="remarks" rows="4" maxlength="60000"><?php echo htmlspecialchars((string) ($_POST['remarks'] ?? $record['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="form-actions form-field-full">
            <a class="button button-quiet" href="testing.php">Cancel</a>
            <button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save test result</button>
        </div>
    </form>
</section>
<?php else: ?>
<section class="panel" data-reveal>
    <div class="empty-state"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><h2>Assignment completed</h2><p>This test already has a final result. View it in the test details page.</p><a class="button button-secondary" href="test-details.php?id=<?php echo (int) $recordId; ?>">View test details</a></div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
