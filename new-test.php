<?php
// Create a pending assignment only from the current product-family plan, then revalidate under locks.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/TestIdGenerator.php';
require_once __DIR__ . '/models/ProductWorkflow.php';
require_once __DIR__ . '/models/TestAssignmentPolicy.php';

$currentRole = (string) ($_SESSION['role'] ?? '');
$canRecordDirectResult = $currentRole === 'Administrator';
$availableProductTests = TestAssignmentPolicy::availableProductTests($conn);
$testerDirectory = TestAssignmentPolicy::testerDirectory($conn);

$productMap = [];
$testTypeMap = [];
foreach ($availableProductTests as $availableProduct) {
    $productMap[(string) $availableProduct['product_id']] = $availableProduct;
    foreach ($availableProduct['tests'] as $availableTest) {
        $typeId = (int) $availableTest['id'];
        if (!isset($testTypeMap[$typeId])) {
            $testTypeMap[$typeId] = $availableTest + ['eligible_products' => []];
        }
        $testTypeMap[$typeId]['eligible_products'][] = (string) $availableProduct['product_id'];
    }
}

$formValues = [
    'product_id' => trim((string) ($_POST['product_id'] ?? '')),
    'test_type_id' => (int) ($_POST['test_type_id'] ?? 0),
    'testing_date' => trim((string) ($_POST['testing_date'] ?? date('Y-m-d'))),
    'criteria' => trim((string) ($_POST['criteria'] ?? '')),
    'expected_output' => trim((string) ($_POST['expected_output'] ?? '')),
    'actual_output' => trim((string) ($_POST['actual_output'] ?? '')),
    'remarks' => trim((string) ($_POST['remarks'] ?? '')),
    'result' => strtoupper(trim((string) ($_POST['result'] ?? 'PENDING'))),
];
$rawTesterIds = $_POST['tester_ids'] ?? [];
if (!is_array($rawTesterIds)) {
    $rawTesterIds = [$rawTesterIds];
}
$selectedTesterIds = array_values(array_unique(array_filter(
    array_map('intval', $rawTesterIds),
    static fn (int $id): bool => $id > 0
)));

$pageError = '';
$pageSuccess = '';
if (!empty($_SESSION['assignment_success'])) {
    $pageSuccess = (string) $_SESSION['assignment_success'];
    unset($_SESSION['assignment_success']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $resultValue = $canRecordDirectResult ? $formValues['result'] : 'PENDING';
    $actualOutput = $canRecordDirectResult ? $formValues['actual_output'] : '';
    $dateValue = DateTime::createFromFormat('!Y-m-d', $formValues['testing_date']);
    $validDate = $dateValue !== false && $dateValue->format('Y-m-d') === $formValues['testing_date'];
    $eligibleTest = $productMap[$formValues['product_id']]['tests'] ?? [];
    $selectedPlanTest = null;
    foreach ($eligibleTest as $planTest) {
        if ((int) $planTest['id'] === $formValues['test_type_id']) {
            $selectedPlanTest = $planTest;
            break;
        }
    }

    if ($formValues['product_id'] === '' || $selectedPlanTest === null) {
        $pageError = 'Choose an available product and a test type from its current family plan.';
    } elseif ($selectedTesterIds === []) {
        $pageError = 'Select at least one available Tester to receive the assignment.';
    } elseif (!$validDate) {
        $pageError = 'Choose a valid testing date.';
    } elseif ($formValues['criteria'] === '' || $formValues['expected_output'] === '') {
        $pageError = 'Enter both the test criteria and expected output before assigning work.';
    } elseif (strlen($formValues['criteria']) > 60000 || strlen($formValues['expected_output']) > 60000 || strlen($formValues['remarks']) > 60000) {
        $pageError = 'Text fields must be 60,000 characters or fewer.';
    } elseif (!in_array($resultValue, ['PASS', 'FAIL', 'PENDING'], true)) {
        $pageError = 'Choose PASS, FAIL, or PENDING as the result.';
    } elseif ($resultValue !== 'PENDING' && $actualOutput === '') {
        $pageError = 'Enter measured actual output for a direct PASS or FAIL result.';
    } elseif (strlen($actualOutput) > 60000) {
        $pageError = 'Actual output must be 60,000 characters or fewer.';
    } else {
        $transactionStarted = false;
        try {
            // Reserve the ID before the assignment transaction; failed inserts may skip, never reuse, a roll.
            $testId = TestIdGenerator::reserve(
                $conn,
                $formValues['product_id'],
                $formValues['test_type_id'],
                (string) $selectedPlanTest['numeric_code']
            );

            $conn->begin_transaction();
            $transactionStarted = true;
            $lockedProduct = TestAssignmentPolicy::lockAssignableProduct(
                $conn,
                $formValues['product_id'],
                $formValues['test_type_id']
            );
            if ((string) $lockedProduct['test_plan_row']['numeric_code'] !== (string) $selectedPlanTest['numeric_code']) {
                throw new DomainException('The registered test ID code changed while you were assigning it. Refresh the page and choose again.');
            }
            $lockedTesters = TestAssignmentPolicy::lockAvailableTesters($conn, $selectedTesterIds);

            $primaryTesterId = (int) $lockedTesters[0]['id'];
            $testCycle = (int) $lockedProduct['current_cycle'];
            $testStatus = $resultValue === 'PENDING' ? 'Pending' : 'Completed';
            $departmentId = (int) ($lockedProduct['test_plan_row']['department_id'] ?? 0);
            $departmentId = $departmentId > 0 ? $departmentId : null;

            $insert = $conn->prepare(
                'INSERT INTO tests (test_id, product_id, test_type_id, tester_id, testing_date, criteria, expected_output, actual_output, result, status, remarks, department_id, cycle_number) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->bind_param(
                'ssiisssssssii',
                $testId,
                $formValues['product_id'],
                $formValues['test_type_id'],
                $primaryTesterId,
                $formValues['testing_date'],
                $formValues['criteria'],
                $formValues['expected_output'],
                $actualOutput,
                $resultValue,
                $testStatus,
                $formValues['remarks'],
                $departmentId,
                $testCycle
            );
            $insert->execute();
            $testRecordId = (int) $conn->insert_id;
            $insert->close();

            $participantInsert = $conn->prepare(
                'INSERT INTO test_participants (test_record_id, tester_id, participant_role) VALUES (?, ?, ?)'
            );
            foreach ($lockedTesters as $index => $tester) {
                $participantTesterId = (int) $tester['id'];
                $participantRole = $index === 0 ? 'Lead' : 'Participant';
                $participantInsert->bind_param('iis', $testRecordId, $participantTesterId, $participantRole);
                $participantInsert->execute();
            }
            $participantInsert->close();

            $changedBy = (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'Lab User');
            ProductWorkflow::applyTestResult(
                $conn,
                $formValues['product_id'],
                (int) $lockedProduct['product_type_id'],
                $testId,
                $testCycle,
                $resultValue,
                $formValues['remarks'],
                $changedBy,
                (string) $lockedProduct['status']
            );
            audit_write($conn, 'test_assignment_created', 'Test', $testId, 'A current-cycle test was assigned to one or more testers.', [
                'record_id' => $testRecordId,
                'product_id' => $formValues['product_id'],
                'test_type_id' => $formValues['test_type_id'],
                'result' => $resultValue,
                'testing_date' => $formValues['testing_date'],
                'cycle_number' => $testCycle,
                'tester_ids' => array_map(static fn (array $tester): int => (int) $tester['id'], $lockedTesters),
            ]);

            $conn->commit();
            $transactionStarted = false;
            $_SESSION['assignment_success'] = 'Test ' . $testId . ' was assigned to ' . count($lockedTesters) . ' tester' . (count($lockedTesters) === 1 ? '' : 's') . '.';
            header('Location: new-test.php', true, 303);
            exit;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackException) {
                    // Preserve the original validation or database error.
                }
            }
            error_log('Lab Automation test assignment failed: ' . $exception->getMessage());
            $pageError = $exception instanceof DomainException || $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'The assignment could not be saved. Refresh the page and verify the selected records.';
        }
    }
}

$pageTitle = 'Assign a test';
$pageEyebrow = 'TEST ASSIGNMENT';
$pageDescription = 'Choose the next eligible test in the product family plan, then assign it to one or more available Testers.';
$pageActionHtml = '<a class="button button-quiet" href="testing.php"><i class="fa-solid fa-list-check" aria-hidden="true"></i> View test records</a>';
$pageStylesheet = 'assets/css/pages/new-test.css';
require __DIR__ . '/views/layouts/app_start.php';
?>

<?php if ($pageError !== ''): ?>
    <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($pageSuccess !== ''): ?>
    <div class="alert alert-success" role="status"><?php echo htmlspecialchars($pageSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($availableProductTests === []): ?>
    <section class="panel assignment-empty" data-reveal>
        <div class="empty-state">
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
            <h2>No test is ready to assign</h2>
            <p>Only active products in a testing stage with an unassigned test from the current family plan appear here. Finish the open test or review the product workflow before assigning the next one.</p>
            <a class="button button-secondary" href="products.php">Review products</a>
        </div>
    </section>
<?php else: ?>
    <form method="post" action="new-test.php" class="assignment-form" data-assignment-form>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <section class="panel assignment-panel" data-reveal>
            <header class="panel-header">
                <div><p class="eyebrow">STEP 01</p><h2>Product and test</h2><p>Unavailable products and tests are removed from this live selection.</p></div>
                <span class="status-pill is-info"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Current-cycle rules</span>
            </header>
            <div class="panel-body">
                <div class="assignment-grid">
                    <div class="form-field">
                        <label for="assignment-product">Product <span class="required-mark">*</span></label>
                        <select id="assignment-product" name="product_id" required>
                            <option value="">Select an eligible product</option>
                            <?php foreach ($availableProductTests as $availableProduct): ?>
                                <option value="<?php echo htmlspecialchars((string) $availableProduct['product_id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $formValues['product_id'] === (string) $availableProduct['product_id'] ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $availableProduct['product_id'] . ' · ' . (string) $availableProduct['product_name'] . ' · Cycle ' . (int) $availableProduct['current_cycle'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint" data-product-hint>Select a product to see its eligible next tests.</small>
                    </div>
                    <div class="form-field">
                        <label for="assignment-test">Test type <span class="required-mark">*</span></label>
                        <select id="assignment-test" name="test_type_id" required>
                            <option value="">Select an eligible test</option>
                            <?php foreach ($testTypeMap as $testId => $testType): ?>
                                <option value="<?php echo (int) $testId; ?>" data-products="<?php echo htmlspecialchars(implode(',', array_values(array_unique($testType['eligible_products']))), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $formValues['test_type_id'] === (int) $testId ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $testType['code'] . ' · ' . (string) $testType['name'] . ' · ' . (string) $testType['department'] . ' · ID ' . (string) $testType['numeric_code'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint" data-test-hint>Test options follow the active family plan and current-cycle sequence.</small>
                    </div>
                    <div class="form-field">
                        <label for="assignment-date">Planned testing date <span class="required-mark">*</span></label>
                        <input id="assignment-date" type="date" name="testing_date" value="<?php echo htmlspecialchars($formValues['testing_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="assignment-rule-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <p>Only one open test can be assigned per product at a time. Once it closes, the next mapped test type that has not been used in this cycle becomes eligible.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel assignment-panel" data-reveal>
            <header class="panel-header">
                <div><p class="eyebrow">STEP 02</p><h2>Assign Tester(s)</h2><p>Search and select one or more available team members.</p></div>
                <span class="tester-count" data-tester-count aria-live="polite">0 selected</span>
            </header>
            <div class="panel-body">
                <label class="search-field tester-search" for="tester-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="tester-search" type="search" placeholder="Search name, department, or role" autocomplete="off" data-tester-search>
                </label>
                <div class="tester-picker" role="group" aria-label="Available tester profiles">
                    <?php foreach ($testerDirectory as $tester): ?>
                        <?php
                            $testerSearchText = strtolower(trim((string) $tester['name'] . ' ' . (string) $tester['department'] . ' ' . (string) $tester['designation']));
                            $testerAvailable = (bool) $tester['available'];
                            $testerId = (int) $tester['id'];
                        ?>
                        <label class="tester-choice<?php echo $testerAvailable ? '' : ' is-unavailable'; ?>" data-tester-choice data-search="<?php echo htmlspecialchars($testerSearchText, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" name="tester_ids[]" value="<?php echo $testerId; ?>" data-tester-checkbox<?php echo in_array($testerId, $selectedTesterIds, true) && $testerAvailable ? ' checked' : ''; ?><?php echo $testerAvailable ? '' : ' disabled'; ?>>
                            <span class="tester-choice-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="tester-choice-copy"><strong><?php echo htmlspecialchars((string) $tester['name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars(trim((string) $tester['designation'] . ((string) $tester['department'] !== '' ? ' · ' . (string) $tester['department'] : '')), ENT_QUOTES, 'UTF-8'); ?></small></span>
                            <?php if ($testerAvailable): ?>
                                <span class="tester-state is-ready">Available</span>
                            <?php else: ?>
                                <span class="tester-state is-busy"><?php echo htmlspecialchars((string) ($tester['unavailable_reason'] ?: 'Unavailable'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="tester-no-results" data-tester-no-results hidden>No tester matches that search.</p>
                    <?php if ($testerDirectory === []): ?>
                        <p class="tester-empty">No active Tester profiles are available. Ask a Lab Manager to review the team profile list.</p>
                    <?php endif; ?>
                </div>
                <p class="form-hint">Busy or inactive accounts cannot be selected. Availability is checked again when you save.</p>
            </div>
        </section>

        <section class="panel assignment-panel" data-reveal>
            <header class="panel-header">
                <div><p class="eyebrow">STEP 03</p><h2>Test instructions</h2><p>Give the assigned team a clear standard and expected measurement.</p></div>
            </header>
            <div class="panel-body">
                <div class="assignment-grid assignment-grid--instructions">
                    <div class="form-field form-field-full">
                        <label for="assignment-criteria">Testing criteria <span class="required-mark">*</span></label>
                        <textarea id="assignment-criteria" name="criteria" rows="4" maxlength="60000" placeholder="Reference the applicable standard, method, and acceptance conditions." required><?php echo htmlspecialchars($formValues['criteria'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-field">
                        <label for="assignment-expected">Expected output <span class="required-mark">*</span></label>
                        <textarea id="assignment-expected" name="expected_output" rows="4" maxlength="60000" placeholder="Describe the expected readings or pass limits." required><?php echo htmlspecialchars($formValues['expected_output'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-field">
                        <label for="assignment-remarks">Notes for the Tester <span class="optional-mark">Optional</span></label>
                        <textarea id="assignment-remarks" name="remarks" rows="4" maxlength="60000" placeholder="Add handling notes or context for this assignment."><?php echo htmlspecialchars($formValues['remarks'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <?php if ($canRecordDirectResult): ?>
                        <div class="form-field">
                            <label for="assignment-result">Record a direct result <span class="optional-mark">Administrator only</span></label>
                            <select id="assignment-result" name="result">
                                <?php foreach (['PENDING' => 'Pending assignment', 'PASS' => 'PASS', 'FAIL' => 'FAIL'] as $resultCode => $resultLabel): ?>
                                    <option value="<?php echo $resultCode; ?>"<?php echo $formValues['result'] === $resultCode ? ' selected' : ''; ?>><?php echo htmlspecialchars($resultLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">For a normal assignment, leave this as Pending. PASS/FAIL requires actual output.</small>
                        </div>
                        <div class="form-field" data-actual-output-field<?php echo $formValues['result'] === 'PENDING' ? ' hidden' : ''; ?>>
                            <label for="assignment-actual">Actual output <span class="required-mark">*</span></label>
                            <textarea id="assignment-actual" name="actual_output" rows="4" maxlength="60000" placeholder="Enter observed results only when recording a direct PASS/FAIL."><?php echo htmlspecialchars($formValues['actual_output'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    <?php else: ?>
                        <div class="assignment-pending-note">
                            <span class="status-pill is-warning"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Pending result</span>
                            <p>The assigned Tester records actual output and PASS/FAIL from their own test queue.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <footer class="assignment-actions">
                <a class="button button-quiet" href="testing.php">Cancel</a>
                <button class="button button-primary" type="submit"<?php echo $testerDirectory === [] || count(array_filter($testerDirectory, static fn (array $tester): bool => (bool) $tester['available'])) === 0 ? ' disabled' : ''; ?>>
                    <i class="fa-solid fa-user-check" aria-hidden="true"></i> Create test assignment
                </button>
            </footer>
        </section>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
