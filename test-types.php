<?php
// Keep this legacy screen functional while enforcing one exact three-digit ID code per test type.
declare(strict_types=1);

require_once __DIR__ . "/config/security.php";
app_start_session();
require_once __DIR__ . "/db.php";
require_roles(['Administrator', 'Lab Manager']);

$message = "";
$message_type = "";
$departmentOptions = $conn->query(
    "SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name"
);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $test_code = strtoupper(trim((string) ($_POST['test_code'] ?? '')));
    $numeric_code = trim((string) ($_POST['numeric_code'] ?? ''));
    $test_name = trim((string) ($_POST['test_name'] ?? ''));
    $department_id = (int) ($_POST['department_id'] ?? 0);
    $description = trim((string) ($_POST['description'] ?? ''));

    if (!preg_match('/^[A-Z0-9_-]{2,16}$/', $test_code) || $test_name === '' || strlen($test_name) > 150) {
        $message = "Enter a valid test code and test name.";
        $message_type = "error";
    } elseif (!preg_match('/^\d{3}$/', $numeric_code)) {
        $message = "The numeric test ID code must be exactly three digits, including leading zeroes (for example, 001).";
        $message_type = "error";
    } elseif ($department_id < 1) {
        $message = "Choose an active department. Add one first if the list is empty.";
        $message_type = "error";
    } else {
        $departmentStmt = $conn->prepare(
            "SELECT department_name FROM departments WHERE id = ? AND is_active = 1 LIMIT 1"
        );
        $departmentStmt->bind_param('i', $department_id);
        $departmentStmt->execute();
        $departmentRow = $departmentStmt->get_result()->fetch_assoc();
        $departmentStmt->close();

        if (!$departmentRow) {
            $message = "The selected department is not active.";
            $message_type = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO test_types (test_code, numeric_code, test_name, department, department_id, description) " .
                    "VALUES (?, ?, ?, ?, ?, ?)"
                );
                $departmentName = (string) $departmentRow['department_name'];
                $stmt->bind_param('ssssis', $test_code, $numeric_code, $test_name, $departmentName, $department_id, $description);
                $stmt->execute();
                $stmt->close();
                $message = "Test Type added successfully.";
                $message_type = "success";
                $_POST = [];
            } catch (mysqli_sql_exception $exception) {
                error_log('Lab Automation test type write failed: ' . $exception->getMessage());
                $message = "That test code or three-digit numeric code is already assigned.";
                $message_type = "error";
            }
        }
    }
}

$result = $conn->query("SELECT * FROM test_types ORDER BY id DESC");
$user_name = $_SESSION['name'] ?? 'Lab Administrator';
$user_role = $_SESSION['role'] ?? 'Administrator';
$name_parts = explode(" ", trim($user_name));
$user_initials = "";
foreach ($name_parts as $part) {
    if ($part !== "") {
        $user_initials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($user_initials) >= 2) {
        break;
    }
}
if ($user_initials === "") {
    $user_initials = "LA";
}
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

<title>Test Types | Lab Automation</title>


<!-- GOOGLE FONTS -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<link rel="stylesheet" href="assets/css/pages/test-types.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-icon">
            ⚡
        </div>

        <div class="brand-text">

            <strong>
                LAB AUTOMATION
            </strong>

            <span>
                Electrical Testing
            </span>

        </div>

    </div>


    <div class="nav-title">
        Main Menu
    </div>


    <nav>


        <a
            href="dashboard.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⌂
            </span>

            Dashboard

        </a>


        <a
            href="products.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ▣
            </span>

            Products

        </a>


        <a
            href="testing.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ◈
            </span>

            Testing

        </a>


        <a
            href="test-types.php"
            class="nav-link active"
        >

            <span class="nav-icon">
                ◫
            </span>

            Test Types

        </a>


        <a
            href="search.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⌕
            </span>

            Advanced Search

        </a>


            <?php if (($_SESSION['role'] ?? '') === 'Administrator'): ?>
            <a href="product-test-plan.php" class="nav-link">
                <span class="nav-icon">✓</span>
                Product Test Plans
            </a>
            <?php endif; ?>

            <a
                href="reports.php"
                class="nav-link"
            >

            <span class="nav-icon">
                ▤
            </span>

            Reports

        </a>


        <a
            href="testers.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ◎
            </span>

            Testers

        </a>


        <a
            href="settings.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⚙
            </span>

            Settings

        </a>


        <a
            href="logout.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⇥
            </span>

            Logout

        </a>


    </nav>


    <!-- USER -->

    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">

                <?php
                echo htmlspecialchars($user_initials);
                ?>

            </div>


            <div class="user-info">

                <strong>

                    <?php
                    echo htmlspecialchars($user_name);
                    ?>

                </strong>

                <span>

                    <?php
                    echo htmlspecialchars($user_role);
                    ?>

                </span>

            </div>

        </div>

    </div>


</aside>



<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <div class="welcome">

            <small>
                TESTING CONFIGURATION
            </small>

            <h1>
                Test Types
            </h1>

            <p>
                Manage laboratory testing categories and procedures.
            </p>

        </div>


        <button
            class="add-btn"
            onclick="toggleForm()"
        >

            + Add New Test Type

        </button>


    </div>



    <!-- MESSAGE -->

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>



    <!-- =========================
         STATS
    ========================= -->

    <div class="stats">


        <div class="stat-card">

            <span>
                Total Test Types
            </span>

            <h2>

                <?php
                echo mysqli_num_rows($result);
                ?>

            </h2>

        </div>



        <div class="stat-card">

            <span>
                Electrical Testing
            </span>

            <h2>

                <?php

                $electrical = mysqli_query(
                    $conn,
                    "SELECT COUNT(*) AS total
                     FROM test_types
                     WHERE department = 'Electrical Testing'"
                );

                $electrical_row =
                    mysqli_fetch_assoc($electrical);

                echo $electrical_row['total'];

                ?>

            </h2>

        </div>



        <div class="stat-card">

            <span>
                Testing Departments
            </span>

            <h2>

                <?php

                $departments = mysqli_query(
                    $conn,
                    "SELECT COUNT(DISTINCT department) AS total
                     FROM test_types
                     WHERE department IS NOT NULL
                     AND department != ''"
                );

                $department_row =
                    mysqli_fetch_assoc($departments);

                echo $department_row['total'];

                ?>

            </h2>

        </div>


    </div>



    <!-- =========================
         ADD FORM
    ========================= -->

    <div
        class="form-card"
        id="addForm"
    >


        <h2>
            Add New Test Type
        </h2>


        <p class="form-subtitle">
            Create a new laboratory testing category.
        </p>



        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Test Code *
                    </label>

                    <input
                        type="text"
                        name="test_code"
                        placeholder="Example: T005"
                        required
                    >

                </div>



                <div class="form-group">

                    <label>
                        Numeric ID Code (3 digits) *
                    </label>

                    <input
                        type="text"
                        name="numeric_code"
                        inputmode="numeric"
                        pattern="[0-9]{3}"
                        maxlength="3"
                        placeholder="Example: 001"
                        value="<?php echo htmlspecialchars($_POST['numeric_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Test Name *
                    </label>

                    <input
                        type="text"
                        name="test_name"
                        placeholder="Example: Temperature Test"
                        required
                    >

                </div>



                <div class="form-group">

                    <label>
                        Department
                    </label>

                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php while ($departmentOption = $departmentOptions->fetch_assoc()): ?>
                            <option value="<?php echo (int) $departmentOption['id']; ?>" <?php echo (int) ($_POST['department_id'] ?? 0) === (int) $departmentOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($departmentOption['department_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small>If the list is empty, <a href="departments.php">add a department</a> first.</small>

                </div>



                <div class="form-group">

                    <label>
                        Description
                    </label>

                    <input
                        type="text"
                        name="description"
                        placeholder="Short description"
                    >

                </div>


            </div>



            <div class="form-actions">


                <button
                    type="button"
                    class="cancel-btn"
                    onclick="toggleForm()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Test Type
                </button>


            </div>


        </form>


    </div>



    <!-- =========================
         TABLE
    ========================= -->

    <div class="table-card">


        <div class="table-header">

            <h2>
                Available Test Types
            </h2>

            <p>
                Laboratory testing categories configured in the system.
            </p>

        </div>



        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Test Code
                        </th>

                        <th>
                            Numeric ID Code
                        </th>

                        <th>
                            Test Name
                        </th>

                        <th>
                            Department
                        </th>

                        <th>
                            Description
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php

                if (mysqli_num_rows($result) > 0):

                    $count = 1;

                    while ($row = mysqli_fetch_assoc($result)):

                ?>


                    <tr>


                        <td>

                            <?php
                            echo $count++;
                            ?>

                        </td>


                        <td class="code">

                            <?php

                            echo htmlspecialchars(
                                $row['test_code']
                            );

                            ?>

                        </td>


                        <td class="code">
                            <?php echo htmlspecialchars((string) ($row['numeric_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['test_name']
                            );

                            ?>

                        </td>


                        <td class="department">

                            <?php

                            echo htmlspecialchars(
                                $row['department']
                            );

                            ?>

                        </td>


                        <td class="description">

                            <?php

                            echo htmlspecialchars(
                                $row['description']
                            );

                            ?>

                        </td>


                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >

                            No Test Types found.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>



<script>

function toggleForm() {

    const form =
        document.getElementById("addForm");

    form.classList.toggle("show");

}

</script>


</body>

</html>