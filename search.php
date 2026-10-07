<?php

include "db.php";
require_page_access(__FILE__);

$results = null;

$product_id = "";
$product_name = "";
$product_code = "";
$test_id = "";
$test_type = "";
$tester = "";
$result_filter = "";
$status = "";
$testing_date = "";
$department_filter = "";
$cycle_filter = "";


/* =========================
   SEARCH
========================= */

if (isset($_GET['search'])) {

    $product_id    = trim($_GET['product_id'] ?? "");
    $product_name  = trim($_GET['product_name'] ?? "");
    $product_code  = trim($_GET['product_code'] ?? "");
    $test_id       = trim($_GET['test_id'] ?? "");
    $test_type     = trim($_GET['test_type'] ?? "");
    $tester        = trim($_GET['tester'] ?? "");
    $result_filter = trim($_GET['result'] ?? "");
    $status        = trim($_GET['status'] ?? "");
    $testing_date  = trim($_GET['testing_date'] ?? "");
    $department_filter = trim($_GET['department_id'] ?? "");
    $cycle_filter = trim($_GET['cycle_number'] ?? "");


    $sql = "
        SELECT
            tests.id,
            tests.test_id,
            tests.product_id,
            tests.testing_date,
            tests.result,
            tests.status,
            tests.remarks,
            tests.cycle_number,
            COALESCE(routed_department.department_name, test_types.department) AS department_name,

            products.product_name,
            products.product_code,
            products.product_type,
            products.revision,
            products.status AS product_status,
            products.cpri_status,

            test_types.test_name,
            test_types.test_code,

            COALESCE((
                SELECT GROUP_CONCAT(DISTINCT participant.name ORDER BY participant.name SEPARATOR ', ')
                FROM test_participants AS participation
                INNER JOIN testers AS participant ON participant.id = participation.tester_id
                WHERE participation.test_record_id = tests.id
            ), testers.name, 'Not Assigned') AS tester_names

        FROM tests
        LEFT JOIN products ON tests.product_id = products.product_id
        LEFT JOIN test_types ON tests.test_type_id = test_types.id
        LEFT JOIN departments AS routed_department ON tests.department_id = routed_department.id
        LEFT JOIN testers ON tests.tester_id = testers.id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    // Testers may search only tests assigned to their linked tester profile.
    if (($_SESSION['role'] ?? '') === 'Tester') {
        $profileStatement = $conn->prepare('SELECT id FROM testers WHERE user_id = ? LIMIT 1');
        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        $profileStatement->bind_param('i', $sessionUserId);
        $profileStatement->execute();
        $linkedProfile = $profileStatement->get_result()->fetch_assoc();
        $profileStatement->close();
        $linkedTesterId = (int) ($linkedProfile['id'] ?? 0);
        if ($linkedTesterId > 0) {
            $sql .= ' AND (tests.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS own_participant WHERE own_participant.test_record_id = tests.id AND own_participant.tester_id = ?))';
            $params[] = $linkedTesterId;
            $params[] = $linkedTesterId;
            $types .= 'ii';
        } else {
            $sql .= ' AND 1 = 0';
        }
    }

    /* Product ID */
    if ($product_id != "") {
        $sql .= " AND tests.product_id LIKE ?";
        $params[] = "%" . $product_id . "%";
        $types .= "s";
    }


    /* Product Name */
    if ($product_name != "") {
        $sql .= " AND products.product_name LIKE ?";
        $params[] = "%" . $product_name . "%";
        $types .= "s";
    }


    /* Product Code */
    if ($product_code != "") {
        $sql .= " AND products.product_code LIKE ?";
        $params[] = "%" . $product_code . "%";
        $types .= "s";
    }


    /* Test ID */
    if ($test_id != "") {
        $sql .= " AND tests.test_id LIKE ?";
        $params[] = "%" . $test_id . "%";
        $types .= "s";
    }


    /* Test Type */
    if ($test_type != "") {
        $sql .= " AND tests.test_type_id = ?";
        $params[] = $test_type;
        $types .= "i";
    }


    /* Tester: include any named participant as well as the legacy lead-tester field. */
    if ($tester != "") {
        $sql .= " AND (tests.tester_id = ? OR EXISTS (SELECT 1 FROM test_participants AS tp WHERE tp.test_record_id = tests.id AND tp.tester_id = ?))";
        $params[] = $tester;
        $params[] = $tester;
        $types .= "ii";
    }

    /* Routed department */
    if ($department_filter !== '' && ctype_digit($department_filter)) {
        $sql .= " AND COALESCE(tests.department_id, test_types.department_id) = ?";
        $params[] = (int) $department_filter;
        $types .= "i";
    }

    /* Re-manufacture/retest cycle */
    if ($cycle_filter !== '' && ctype_digit($cycle_filter)) {
        $sql .= " AND tests.cycle_number = ?";
        $params[] = (int) $cycle_filter;
        $types .= "i";
    }


    /* Result */
    if ($result_filter != "") {
        $sql .= " AND tests.result = ?";
        $params[] = $result_filter;
        $types .= "s";
    }


    /* Status */
    if ($status != "") {
        $sql .= " AND tests.status = ?";
        $params[] = $status;
        $types .= "s";
    }


    /* Testing Date */
    if ($testing_date != "") {
        $sql .= " AND tests.testing_date = ?";
        $params[] = $testing_date;
        $types .= "s";
    }


    $sql .= " ORDER BY tests.id DESC";


    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        if (!empty($params)) {
            mysqli_stmt_bind_param(
                $stmt,
                $types,
                ...$params
            );
        }

        mysqli_stmt_execute($stmt);

        $results = mysqli_stmt_get_result($stmt);
    }
}


/* =========================
   TEST TYPES
========================= */

$test_types = mysqli_query(
    $conn,
    "SELECT id, test_code, test_name
     FROM test_types
     ORDER BY test_name ASC"
);


/* =========================
   TESTERS
========================= */

$testers = mysqli_query(
    $conn,
    "SELECT id, name FROM testers WHERE is_active = 1 ORDER BY name ASC"
);

$departments = mysqli_query(
    $conn,
    "SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Advanced Search | Lab Automation</title>

<link rel="stylesheet" href="assets/css/pages/search.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<?php require __DIR__ . '/views/layouts/legacy_sidebar.php'; ?>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <div class="header">

        <h1>
            Advanced Search
        </h1>

        <p>
            Search products and laboratory testing records.
        </p>

    </div>


    <!-- =========================
         SEARCH FORM
    ========================= -->

    <section class="search-card">

        <h2 class="search-title">
            Search Testing Records
        </h2>


        <form method="GET">


            <div class="form-grid">


                <!-- Product ID -->

                <div class="field">

                    <label>
                        Product ID
                    </label>

                    <input
                        type="text"
                        name="product_id"
                        placeholder="Enter Product ID"
                        value="<?php echo htmlspecialchars($product_id); ?>"
                    >

                </div>


                <!-- Product Name -->

                <div class="field">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        placeholder="Enter Product Name"
                        value="<?php echo htmlspecialchars($product_name); ?>"
                    >

                </div>


                <!-- Product Code -->

                <div class="field">

                    <label>
                        Product Code
                    </label>

                    <input
                        type="text"
                        name="product_code"
                        placeholder="Enter Product Code"
                        value="<?php echo htmlspecialchars($product_code); ?>"
                    >

                </div>


                <!-- Test ID -->

                <div class="field">

                    <label>
                        Test ID
                    </label>

                    <input
                        type="text"
                        name="test_id"
                        placeholder="Enter Test ID"
                        value="<?php echo htmlspecialchars($test_id); ?>"
                    >

                </div>


                <!-- Test Type -->

                <div class="field">

                    <label>
                        Test Type
                    </label>

                    <select name="test_type">

                        <option value="">
                            All Test Types
                        </option>

                        <?php

                        if ($test_types) {

                            while ($type = mysqli_fetch_assoc($test_types)) {

                        ?>

                            <option
                                value="<?php echo $type['id']; ?>"
                                <?php
                                if ($test_type == $type['id']) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $type['test_code']
                                    . " - "
                                    . $type['test_name']
                                );
                                ?>

                            </option>

                        <?php

                            }

                        }

                        ?>

                    </select>

                </div>


                <!-- Department -->
                <div class="field">
                    <label>Routed Department</label>
                    <select name="department_id">
                        <option value="">All Departments</option>
                        <?php if ($departments): while ($department_row = mysqli_fetch_assoc($departments)): ?>
                            <option value="<?php echo (int) $department_row['id']; ?>" <?php echo $department_filter == $department_row['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department_row['department_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <!-- Test cycle -->
                <div class="field">
                    <label>Test Cycle</label>
                    <input type="number" name="cycle_number" min="1" max="999" placeholder="e.g. 2" value="<?php echo htmlspecialchars($cycle_filter, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Tester -->

                <div class="field">

                    <label>
                        Tester
                    </label>

                    <select name="tester">

                        <option value="">
                            All Testers
                        </option>

                        <?php

                        if ($testers) {

                            while ($tester_row = mysqli_fetch_assoc($testers)) {

                        ?>

                            <option
                                value="<?php echo $tester_row['id']; ?>"
                                <?php
                                if ($tester == $tester_row['id']) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $tester_row['name']
                                );
                                ?>

                            </option>

                        <?php

                            }

                        }

                        ?>

                    </select>

                </div>


                <!-- Result -->

                <div class="field">

                    <label>
                        Result
                    </label>

                    <select name="result">

                        <option value="">
                            All Results
                        </option>

                        <option
                            value="PASS"
                            <?php
                            if ($result_filter == "PASS") {
                                echo "selected";
                            }
                            ?>
                        >
                            PASS
                        </option>

                        <option
                            value="FAIL"
                            <?php
                            if ($result_filter == "FAIL") {
                                echo "selected";
                            }
                            ?>
                        >
                            FAIL
                        </option>

                        <option
                            value="PENDING"
                            <?php
                            if ($result_filter == "PENDING") {
                                echo "selected";
                            }
                            ?>
                        >
                            PENDING
                        </option>

                    </select>

                </div>


                <!-- Status -->

                <div class="field">

                    <label>
                        Testing Status
                    </label>

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Pending"
                            <?php
                            if ($status == "Pending") {
                                echo "selected";
                            }
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="In Progress"
                            <?php
                            if ($status == "In Progress") {
                                echo "selected";
                            }
                            ?>
                        >
                            In Progress
                        </option>

                        <option
                            value="Completed"
                            <?php
                            if ($status == "Completed") {
                                echo "selected";
                            }
                            ?>
                        >
                            Completed
                        </option>

                    </select>

                </div>


                <!-- Date -->

                <div class="field">

                    <label>
                        Testing Date
                    </label>

                    <input
                        type="date"
                        name="testing_date"
                        value="<?php echo htmlspecialchars($testing_date); ?>"
                    >

                </div>


            </div>


            <div class="buttons">

                <button
                    type="submit"
                    name="search"
                    class="search-btn"
                >
                    Search Records
                </button>


                <a
                    href="search.php"
                    class="reset-btn"
                >
                    Reset
                </a>

            </div>


        </form>

    </section>


    <!-- =========================
         RESULTS
    ========================= -->

    <?php if ($results !== null) { ?>


    <section class="results-card">


        <div class="results-header">

            <h2>
                Search Results
            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>Test ID</th>

                        <th>Product ID</th>

                        <th>Product</th>

                        <th>Product Code</th>

                        <th>Test Type</th>

                        <th>Department</th>

                        <th>Cycle</th>

                        <th>Tester(s)</th>

                        <th>Date</th>

                        <th>Result</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (mysqli_num_rows($results) > 0) {

                    while ($row = mysqli_fetch_assoc($results)) {

                        $result_class = "";

                        if ($row['result'] == "PASS") {

                            $result_class = "pass";

                        }
                        elseif ($row['result'] == "FAIL") {

                            $result_class = "fail";

                        }
                        else {

                            $result_class = "pending";

                        }

                ?>

                    <tr>

                        <td class="id-text">

                            <?php
                            echo htmlspecialchars(
                                $row['test_id']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['product_id']
                            );
                            ?>

                        </td>


                        <td class="product-name">

                            <?php
                            echo htmlspecialchars(
                                $row['product_name']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['product_code']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['test_name']
                            );
                            ?>

                        </td>


                        <td><?php echo htmlspecialchars((string) ($row['department_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>

                        <td><?php echo (int) ($row['cycle_number'] ?? 1); ?></td>

                        <td><?php echo htmlspecialchars((string) ($row['tester_names'] ?? 'Not Assigned'), ENT_QUOTES, 'UTF-8'); ?></td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['testing_date'] ?: "—"
                            );
                            ?>

                        </td>


                        <td>

                            <span class="badge <?php echo $result_class; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['result'] ?: "PENDING"
                                );
                                ?>

                            </span>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['status']
                            );
                            ?>

                        </td>


                        <td>

                            <a
                                href="test-details.php?id=<?php echo $row['id']; ?>"
                                class="view-btn"
                            >
                                View
                            </a>

                        </td>

                    </tr>

                <?php

                    }

                }
                else {

                ?>

                    <tr>

                        <td
                            colspan="12"
                            class="empty"
                        >

                            No matching testing records found.

                        </td>

                    </tr>

                <?php } ?>


                </tbody>

            </table>

        </div>

    </section>


    <?php } ?>


</main>


</body>

</html>