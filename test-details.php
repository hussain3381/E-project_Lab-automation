<?php

include "db.php";
require_page_access(__FILE__);
require_once __DIR__ . '/models/Tester.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid test request.");
}

$id = intval($_GET['id']);

$sql = "SELECT
            tests.*,
            products.product_name,
            products.product_code,
            products.product_type,
            products.revision,
            products.status AS product_status,
            products.cpri_status,
            test_types.test_code,
            test_types.test_name,
            COALESCE(routed_department.department_name, test_types.department) AS department,
            testers.name AS tester_name,
            testers.designation AS tester_designation
        FROM tests
        LEFT JOIN products ON tests.product_id = products.product_id
        LEFT JOIN test_types ON tests.test_type_id = test_types.id
        LEFT JOIN departments AS routed_department ON tests.department_id = routed_department.id
        LEFT JOIN testers ON tests.tester_id = testers.id
        WHERE tests.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($result);

if (!$test) {
    http_response_code(404);
    exit('Test record not found.');
}

// A Tester may only open a record linked to their own tester profile.
$isTester = (string) ($_SESSION['role'] ?? '') === 'Tester';
if ($isTester) {
    $testerProfile = Tester::findByUserId($conn, (int) ($_SESSION['user_id'] ?? 0));
    if ($testerProfile === null) {
        RoleMiddleware::handle([]);
    }
    $ownership = $conn->prepare(
        'SELECT 1 FROM tests AS t WHERE t.id = ? AND (t.tester_id = ? OR EXISTS (' .
        'SELECT 1 FROM test_participants AS p WHERE p.test_record_id = t.id AND p.tester_id = ?)) LIMIT 1'
    );
    $testerProfileId = (int) $testerProfile['id'];
    $ownership->bind_param('iii', $id, $testerProfileId, $testerProfileId);
    $ownership->execute();
    $isAssigned = $ownership->get_result()->num_rows > 0;
    $ownership->close();
    if (!$isAssigned) {
        RoleMiddleware::handle([]);
    }
}

$participantStmt = $conn->prepare(
    'SELECT testers.name, testers.designation FROM test_participants ' .
    'INNER JOIN testers ON testers.id = test_participants.tester_id ' .
    'WHERE test_participants.test_record_id = ? ORDER BY test_participants.participant_role, testers.name'
);
$participantStmt->bind_param('i', $id);
$participantStmt->execute();
$participantResult = $participantStmt->get_result();
$participantLabels = [];
while ($participant = $participantResult->fetch_assoc()) {
    $participantLabels[] = trim((string) $participant['name'] . ((string) $participant['designation'] !== '' ? ' (' . $participant['designation'] . ')' : ''));
}
$participantStmt->close();
if ($participantLabels === [] && (string) ($test['tester_name'] ?? '') !== '') {
    $participantLabels[] = trim((string) $test['tester_name'] . ((string) ($test['tester_designation'] ?? '') !== '' ? ' (' . $test['tester_designation'] . ')' : ''));
}
$participantDisplay = $participantLabels !== [] ? implode(', ', array_unique($participantLabels)) : 'Not assigned';

function statusClass($status) {
    $status = strtolower($status);

    if ($status == "passed" || $status == "pass" || $status == "completed") {
        return "success";
    }

    if ($status == "failed" || $status == "fail") {
        return "danger";
    }

    if ($status == "pending") {
        return "warning";
    }

    return "info";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Test Details | Lab Automation</title>
<link rel="stylesheet" href="assets/css/pages/test-details.css">

</head>

<body>

<!-- SIDEBAR -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>


<!-- MAIN -->

<div class="main">

    <div class="top-bar">

        <div class="page-title">
            <h1>Test Details</h1>
            <p>Complete laboratory testing record</p>
        </div>

        <div class="action-row">
            <?php if ($isTester && strtoupper((string) $test['result']) === 'PENDING' && in_array(strtolower((string) $test['status']), ['pending', 'in progress', 'testing in progress'], true)): ?>
                <a href="complete-test.php?id=<?php echo (int) $test['id']; ?>" class="back-btn">Record assigned result</a>
            <?php endif; ?>
            <a href="testing.php" class="back-btn">← Back to Testing</a>
        </div>

    </div>


    <!-- TEST HEADER -->

    <div class="test-header">

        <div>
            <div class="test-id-label">TEST IDENTIFICATION</div>

            <div class="test-id">
                <?php echo htmlspecialchars($test['test_id']); ?>
            </div>
        </div>

        <div class="badges">

            <span class="badge <?php echo statusClass($test['result']); ?>">
                Result:
                <?php echo htmlspecialchars($test['result']); ?>
            </span>

            <span class="badge <?php echo statusClass($test['status']); ?>">
                <?php echo htmlspecialchars($test['status']); ?>
            </span>

        </div>

    </div>


    <div class="grid">

        <!-- TEST INFORMATION -->

        <div class="card">

            <h3>Test Information</h3>

            <div class="info-grid">

                <div class="info-item">
                    <label>Test ID</label>
                    <p><?php echo htmlspecialchars($test['test_id']); ?></p>
                </div>

                <div class="info-item">
                    <label>Testing Date</label>
                    <p>
                        <?php
                        echo !empty($test['testing_date'])
                            ? date("d M Y", strtotime($test['testing_date']))
                            : "Not specified";
                        ?>
                    </p>
                </div>

                <div class="info-item">
                    <label>Test Code</label>
                    <p><?php echo htmlspecialchars($test['test_code']); ?></p>
                </div>

                <div class="info-item">
                    <label>Test Type</label>
                    <p><?php echo htmlspecialchars($test['test_name']); ?></p>
                </div>

                <div class="info-item">
                    <label>Department</label>
                    <p><?php echo htmlspecialchars($test['department']); ?></p>
                </div>

                <div class="info-item">
                    <label>Tester(s)</label>
                    <p><?php echo htmlspecialchars($participantDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="info-item">
                    <label>Test cycle</label>
                    <p><?php echo (int) ($test['cycle_number'] ?? 1); ?></p>
                </div>

                <div class="info-item">
                    <label>Product workflow</label>
                    <p><?php echo htmlspecialchars((string) ($test['product_status'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

            </div>

        </div>


        <!-- PRODUCT INFORMATION -->

        <div class="card">

            <h3>Product Information</h3>

            <div class="info-grid">

                <div class="info-item">
                    <label>Product ID</label>
                    <p><?php echo htmlspecialchars($test['product_id']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Name</label>
                    <p><?php echo htmlspecialchars($test['product_name']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Code</label>
                    <p><?php echo htmlspecialchars($test['product_code']); ?></p>
                </div>

                <div class="info-item">
                    <label>Product Type</label>
                    <p><?php echo htmlspecialchars($test['product_type']); ?></p>
                </div>

                <div class="info-item">
                    <label>Revision</label>
                    <p><?php echo htmlspecialchars($test['revision']); ?></p>
                </div>

            </div>

        </div>


        <!-- CRITERIA -->

        <div class="card full">

            <h3>Testing Criteria</h3>

            <div class="text-box">
                <?php
                echo !empty($test['criteria'])
                    ? nl2br(htmlspecialchars($test['criteria']))
                    : "No testing criteria provided.";
                ?>
            </div>

        </div>


        <!-- EXPECTED OUTPUT -->

        <div class="card">

            <h3>Expected Output</h3>

            <div class="text-box">
                <?php
                echo !empty($test['expected_output'])
                    ? nl2br(htmlspecialchars($test['expected_output']))
                    : "No expected output provided.";
                ?>
            </div>

        </div>


        <!-- ACTUAL OUTPUT -->

        <div class="card">

            <h3>Actual Output</h3>

            <div class="text-box">
                <?php
                echo !empty($test['actual_output'])
                    ? nl2br(htmlspecialchars($test['actual_output']))
                    : "No actual output provided.";
                ?>
            </div>

        </div>


        <!-- REMARKS -->

        <div class="card full">

            <h3>Testing Remarks</h3>

            <div class="text-box">
                <?php
                echo !empty($test['remarks'])
                    ? nl2br(htmlspecialchars($test['remarks']))
                    : "No remarks provided.";
                ?>
            </div>

        </div>

    </div>


    <div class="footer">
        Lab Automation System © 2026 | Electrical Testing Laboratory
    </div>

</div>

</body>
</html>