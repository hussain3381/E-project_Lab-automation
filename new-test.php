<?php
// Save a test result with a locked sequence, assigned department, participants, and workflow update.
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_roles(['Administrator', 'Lab Manager', 'Quality Control', 'Tester']);
require_once __DIR__ . "/models/TestIdGenerator.php";
require_once __DIR__ . "/models/ProductWorkflow.php";

$message = "";
$message_type = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $product_id = trim((string) ($_POST['product_id'] ?? ''));
    $test_type_id = (int) ($_POST['test_type_id'] ?? 0);
    $rawTesterIds = $_POST['tester_ids'] ?? ($_POST['tester_id'] ?? []);
    if (!is_array($rawTesterIds)) {
        $rawTesterIds = [$rawTesterIds];
    }
    $tester_ids = array_values(array_unique(array_filter(array_map('intval', $rawTesterIds), static fn (int $id): bool => $id > 0)));
    $testing_date = trim((string) ($_POST['testing_date'] ?? ''));
    $criteria = trim((string) ($_POST['criteria'] ?? ''));
    $expected_output = trim((string) ($_POST['expected_output'] ?? ''));
    $actual_output = trim((string) ($_POST['actual_output'] ?? ''));
    $result_value = strtoupper(trim((string) ($_POST['result'] ?? '')));
    $remarks = trim((string) ($_POST['remarks'] ?? ''));

    $dateValue = DateTime::createFromFormat('!Y-m-d', $testing_date);
    $validDate = $dateValue !== false && $dateValue->format('Y-m-d') === $testing_date;

    if ($product_id === '' || $test_type_id < 1 || $tester_ids === [] || !$validDate || $criteria === '' || $expected_output === '') {
        $message = "Choose a product, test type, date, and at least one tester; enter the criteria and expected output.";
        $message_type = "error";
    } elseif (!in_array($result_value, ['PASS', 'FAIL', 'PENDING'], true)) {
        $message = "Choose PASS, FAIL, or PENDING as the test result.";
        $message_type = "error";
    } elseif ($result_value !== 'PENDING' && $actual_output === '') {
        $message = "Enter the actual output for a PASS or FAIL result.";
        $message_type = "error";
    } else {
        $productStmt = $conn->prepare(
            'SELECT product_id, product_name, product_type_id, status, rework_cycle FROM products WHERE product_id = ? LIMIT 1'
        );
        $productStmt->bind_param('s', $product_id);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();
        $productStmt->close();

        $typeStmt = $conn->prepare(
            'SELECT id, test_code, numeric_code, test_name, department, department_id ' .
            'FROM test_types WHERE id = ? AND is_active = 1 LIMIT 1'
        );
        $typeStmt->bind_param('i', $test_type_id);
        $typeStmt->execute();
        $testType = $typeStmt->get_result()->fetch_assoc();
        $typeStmt->close();

        $testersById = [];
        $activeTesterRows = $conn->query('SELECT id, name, department FROM testers WHERE is_active = 1');
        while ($testerRow = $activeTesterRows->fetch_assoc()) {
            $testersById[(int) $testerRow['id']] = $testerRow;
        }
        $selectedTesters = [];
        foreach ($tester_ids as $testerId) {
            if (!isset($testersById[$testerId])) {
                $selectedTesters = [];
                break;
            }
            $selectedTesters[] = $testersById[$testerId];
        }

        if (!$product) {
            $message = "Selected product was not found.";
            $message_type = "error";
        } elseif (!$testType || !preg_match('/^\d{3}$/', (string) ($testType['numeric_code'] ?? ''))) {
            $message = "Selected test type is inactive or has no valid three-digit numeric ID code.";
            $message_type = "error";
        } elseif (in_array((string) $product['status'], ['Failed - Re-manufacturing', 'CPRI Ready', 'Handed to CPRI'], true)) {
            $message = (string) $product['status'] === 'Failed - Re-manufacturing'
                ? "This product is waiting for re-manufacture to be recorded before retesting."
                : "This product has completed laboratory testing. Use the workflow controls for further action.";
            $message_type = "error";
        } elseif (count($selectedTesters) !== count($tester_ids)) {
            $message = "Choose active testers only.";
            $message_type = "error";
        } else {
            $mappingStmt = $conn->prepare(
                'SELECT 1 FROM product_type_test_types WHERE product_type_id = ? AND test_type_id = ? LIMIT 1'
            );
            $productTypeId = (int) $product['product_type_id'];
            $mappingStmt->bind_param('ii', $productTypeId, $test_type_id);
            $mappingStmt->execute();
            $isAssignedTest = $mappingStmt->get_result()->num_rows > 0;
            $mappingStmt->close();

            if (!$isAssignedTest) {
                $message = "This test type is not assigned to the product family test plan. Ask an Administrator to configure the plan.";
                $message_type = "error";
            } else {
                try {
                    // The sequence is committed first; an insert failure may skip a number but can never reuse one.
                    $test_id = TestIdGenerator::reserve(
                        $conn,
                        $product_id,
                        $test_type_id,
                        (string) $testType['numeric_code']
                    );

                    $conn->begin_transaction();
                    $transactionStarted = true;

                    $lock = $conn->prepare(
                        'SELECT product_type_id, status, rework_cycle FROM products WHERE product_id = ? FOR UPDATE'
                    );
                    $lock->bind_param('s', $product_id);
                    $lock->execute();
                    $lockedProduct = $lock->get_result()->fetch_assoc();
                    $lock->close();

                    if (!$lockedProduct || in_array((string) $lockedProduct['status'], ['Failed - Re-manufacturing', 'CPRI Ready', 'Handed to CPRI'], true)) {
                        throw new DomainException('Product workflow changed while this test was being prepared. Reload and try again.');
                    }

                    $testCycle = (int) $lockedProduct['rework_cycle'] + 1;
                    $primaryTesterId = (int) $tester_ids[0];
                    $testStatus = $result_value === 'PENDING' ? 'Pending' : 'Completed';
                    $departmentId = (int) ($testType['department_id'] ?? 0);
                    $departmentId = $departmentId > 0 ? $departmentId : null;

                    $insert = $conn->prepare(
                        'INSERT INTO tests (test_id, product_id, test_type_id, tester_id, testing_date, ' .
                        'criteria, expected_output, actual_output, result, status, remarks, department_id, cycle_number) ' .
                        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insert->bind_param(
                        'ssiisssssssii',
                        $test_id,
                        $product_id,
                        $test_type_id,
                        $primaryTesterId,
                        $testing_date,
                        $criteria,
                        $expected_output,
                        $actual_output,
                        $result_value,
                        $testStatus,
                        $remarks,
                        $departmentId,
                        $testCycle
                    );
                    $insert->execute();
                    $testRecordId = (int) $conn->insert_id;
                    $insert->close();

                    $participant = $conn->prepare(
                        'INSERT INTO test_participants (test_record_id, tester_id, participant_role) VALUES (?, ?, ?)'
                    );
                    foreach ($tester_ids as $index => $testerId) {
                        $participantRole = $index === 0 ? 'Lead' : 'Participant';
                        $participant->bind_param('iis', $testRecordId, $testerId, $participantRole);
                        $participant->execute();
                    }
                    $participant->close();

                    $changedBy = (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'Lab User');
                    ProductWorkflow::applyTestResult(
                        $conn,
                        $product_id,
                        (int) $lockedProduct['product_type_id'],
                        $test_id,
                        $testCycle,
                        $result_value,
                        $remarks,
                        $changedBy,
                        (string) $lockedProduct['status']
                    );

                    $conn->commit();
                    $transactionStarted = false;
                    $message = "Test record saved successfully. Test ID: " . $test_id;
                    $message_type = "success";
                    $_POST = [];
                } catch (Throwable $exception) {
                    if (!empty($transactionStarted)) {
                        $conn->rollback();
                    }
                    error_log('Lab Automation test save failed: ' . $exception->getMessage());
                    $message = $exception instanceof DomainException || $exception instanceof InvalidArgumentException
                        ? $exception->getMessage()
                        : "The test could not be saved. Check the selected records and try again.";
                    $message_type = "error";
                }
            }
        }
    }
}

$products = $conn->query(
    "SELECT product_id, product_name, product_code, revision, status FROM products ORDER BY id DESC"
);
$test_types = $conn->query(
    "SELECT id, test_code, numeric_code, test_name, department FROM test_types " .
    "WHERE is_active = 1 ORDER BY id ASC"
);
$testers = $conn->query(
    "SELECT id, name, department, designation FROM testers WHERE is_active = 1 ORDER BY name ASC"
);
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Start New Test | Lab Automation</title>


<link rel="stylesheet" href="assets/css/pages/new-test.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        <h2>LAB AUTOMATION</h2>

        <p>Electrical Testing System</p>

    </div>


    <div class="menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="testing.php" class="active">
            Testing
        </a>

        <a href="test-types.php">
            Test Types
        </a>

        <a href="search.php">
            Advanced Search
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="testers.php">
            Testers
        </a>

        <a href="settings.php">
            Settings
        </a>

        <a href="login.php">
            Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <div class="header">

        <h1>Start New Test</h1>

        <p>
            Create a new laboratory testing record.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="new-test.php"
    >
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">


        <div class="form-box">


            <!-- =========================
                 TEST INFORMATION
            ========================= -->

            <div class="form-section">

                <div class="section-title">
                    Test Information
                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Product
                            <span class="required">*</span>
                        </label>


                        <select
                            name="product_id"
                            required
                        >

                            <option value="">
                                Select Product
                            </option>


                            <?php while (
                                $product =
                                mysqli_fetch_assoc($products)
                            ): ?>

                                <option
                                    value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_id']
                                    );
                                    ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>


                        <div class="info-box">

                            Select the product that needs
                            laboratory testing.

                        </div>

                    </div>


                    <div class="form-group">

                        <label>
                            Test Type
                            <span class="required">*</span>
                        </label>


                        <select
                            name="test_type_id"
                            required
                        >

                            <option value="">
                                Select Test Type
                            </option>


                            <?php while (
                                $type =
                                mysqli_fetch_assoc($test_types)
                            ): ?>

                                <option
                                    value="<?php echo $type['id']; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $type['test_code'] . ' / ID ' . $type['numeric_code'] . ' - ' .
                                        $type['test_name'] . ' (' . ($type['department'] ?? 'Unassigned') . ')',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>
                    </div>


                    <div class="form-group">

                        <label>
                            Tester(s)
                            <span class="required">*</span>
                        </label>


                        <select
                            name="tester_ids[]"
                            multiple
                            size="4"
                            required
                        >

                            <option value="">
                                Select one or more testers (Ctrl/Cmd-click)
                            </option>


                            <?php while (
                                $tester =
                                mysqli_fetch_assoc($testers)
                            ): ?>

                                <option
                                    value="<?php echo $tester['id']; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $tester['name']
                                    );
                                    ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $tester['designation']
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>
                        <small>Choose all participants; the selected test type routes the record to its department.</small>

                    </div>


                    <div class="form-group">

                        <label>
                            Testing Date
                            <span class="required">*</span>
                        </label>


                        <input
                            type="date"
                            name="testing_date"
                            value="<?php echo date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                </div>

            </div>


            <!-- =========================
                 TESTING DETAILS
            ========================= -->

            <div class="form-section">

                <div class="section-title">
                    Testing Details
                </div>


                <div class="form-grid">


                    <div class="form-group full">

                        <label>
                            Testing Criteria
                            <span class="required">*</span>
                        </label>


                        <textarea
                            name="criteria"
                            placeholder="Enter the criteria or standard against which the product will be tested..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Expected Output
                            <span class="required">*</span>
                        </label>


                        <textarea
                            name="expected_output"
                            placeholder="Enter expected testing output..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Actual Output <span>(required for PASS/FAIL)</span>
                        </label>


                        <textarea
                            name="actual_output"
                            placeholder="Required for PASS or FAIL; optional while pending"
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Result
                            <span class="required">*</span>
                        </label>


                        <select
                            name="result"
                            required
                        >

                            <option value="">
                                Select Result
                            </option>

                            <option value="PASS">
                                PASS
                            </option>

                            <option value="FAIL">
                                FAIL
                            </option>

                            <option value="PENDING">
                                PENDING
                            </option>

                        </select>

                    </div>

                    <div class="form-group full test-status-note">Test record status is generated automatically from the selected result.</div>

                </div>

            </div>


            <!-- =========================
                 BUTTONS
            ========================= -->

            <div class="form-actions">

                <a
                    href="testing.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Test Record
                </button>

            </div>


        </div>

    </form>


</div>


</body>

</html>