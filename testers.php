<?php

include "db.php";

// Apply the minimum role boundary for this module.
require_roles(['Administrator', 'Lab Manager']);

$message = "";
$error = "";

/* =========================
   ADD TESTER
========================= */

if (isset($_POST['add_tester'])) {

    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $designation = trim($_POST['designation']);

    if ($name == "") {

        $error = "Tester name is required.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO testers (name, department, designation)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $name,
            $department,
            $designation
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Tester added successfully!";
        } else {
            $error = "Failed to add tester.";
        }

        mysqli_stmt_close($stmt);
    }
}


/* =========================
   DELETE TESTER
========================= */

if (isset($_POST['delete_tester']) && ctype_digit((string) $_POST['delete_tester'])) {

    $id = (int) $_POST['delete_tester'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM testers WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {

        $message = "Tester deleted successfully!";

    } else {

        $error = "Tester cannot be deleted. This tester may already be linked with a test record.";
    }

    mysqli_stmt_close($stmt);
}


/* =========================
   GET TESTER COUNTS
========================= */

$total_testers = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM testers"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_testers = $row['total'];
}


/* =========================
   GET DEPARTMENTS
========================= */

$departments = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT department) AS total
     FROM testers
     WHERE department IS NOT NULL
     AND department != ''"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $departments = $row['total'];
}


/* =========================
   GET TESTERS
========================= */

$testers = mysqli_query(
    $conn,
    "SELECT * FROM testers ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Testers | Lab Automation</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
rel="stylesheet"
>


<link rel="stylesheet" href="assets/css/pages/testers.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        </div>

        <div class="brand-text">

            <strong>LAB AUTOMATION</strong>

            <span>Electrical Testing</span>

        </div>

    </div>


    <div class="nav-title">
        Main Menu
    </div>


    <nav class="nav">

        <a href="dashboard.php" class="nav-link">

            <span class="nav-icon">⌂</span>

            <span>Dashboard</span>

        </a>


        <a href="products.php" class="nav-link">

            <span class="nav-icon">▣</span>

            <span>Products</span>

        </a>


        <a href="testing.php" class="nav-link">

            <span class="nav-icon">✓</span>

            <span>Testing</span>

        </a>


        <a href="test-types.php" class="nav-link">

            <span class="nav-icon">▤</span>

            <span>Test Types</span>

        </a>


        <a href="search.php" class="nav-link">

            <span class="nav-icon">⌕</span>

            <span>Advanced Search</span>

        </a>


        <a href="reports.php" class="nav-link">

            <span class="nav-icon">▥</span>

            <span>Reports</span>

        </a>


        <a href="testers.php" class="nav-link active">

            <span class="nav-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>

            <span>Testers</span>

        </a>


        <a href="settings.php" class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>

            <span>Settings</span>

        </a>


        <a href="logout.php" class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span>

            <span>Logout</span>

        </a>

    </nav>


    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">
                LA
            </div>

            <div class="user-info">

                <strong>Lab Administrator</strong>

                <span>Administrator</span>

            </div>

        </div>

    </div>

</aside>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <div class="header">

        <div>

            <h1>Testers Management</h1>

            <p>Manage laboratory testers and testing staff.</p>

        </div>


        <a href="#addTester" class="add-btn">
            + Add Tester
        </a>

    </div>


    <?php if ($message != "") { ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <?php if ($error != "") { ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <!-- STATS -->

    <section class="stats">

        <div class="stat-card">

            <span>Total Testers</span>

            <h2>
                <?php echo $total_testers; ?>
            </h2>

        </div>


        <div class="stat-card">

            <span>Testing Departments</span>

            <h2>
                <?php echo $departments; ?>
            </h2>

        </div>

    </section>



    <!-- ADD TESTER FORM -->

    <section class="form-card" id="addTester">

        <h2>Add New Tester</h2>

        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-grid">

                <div class="field">

                    <label>Tester Name *</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter tester name"
                        required
                    >

                </div>


                <div class="field">

                    <label>Department</label>

                    <select name="department">

                        <option value="">Select Department</option>

                        <option value="Electrical Testing">
                            Electrical Testing
                        </option>

                        <option value="Safety Testing">
                            Safety Testing
                        </option>

                        <option value="Performance Testing">
                            Performance Testing
                        </option>

                        <option value="Quality Control">
                            Quality Control
                        </option>

                    </select>

                </div>


                <div class="field">

                    <label>Designation</label>

                    <input
                        type="text"
                        name="designation"
                        placeholder="e.g. Senior Tester"
                    >

                </div>

            </div>


            <div class="form-submit">

                <button type="submit" name="add_tester">
                    Save Tester
                </button>

            </div>

        </form>

    </section>



    <!-- TESTERS TABLE -->

    <section class="table-card">

        <div class="table-header">

            <h2>Laboratory Testers</h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Tester Name</th>

                        <th>Department</th>

                        <th>Designation</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if ($testers && mysqli_num_rows($testers) > 0) {

                    $count = 1;

                    while ($row = mysqli_fetch_assoc($testers)) {

                ?>

                    <tr>

                        <td>
                            <?php echo $count++; ?>
                        </td>

                        <td class="tester-name">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>

                        <td class="department">

                            <?php

                            echo htmlspecialchars(
                                $row['department'] ?: '—'
                            );

                            ?>

                        </td>

                        <td class="designation">

                            <?php

                            echo htmlspecialchars(
                                $row['designation'] ?: '—'
                            );

                            ?>

                        </td>

                        <td>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this tester?');">
                                <!-- The shared CSRF check also protects destructive actions. -->
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="delete_tester" value="<?php echo (int) $row['id']; ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td colspan="5" class="empty">

                            No testers found.

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


</body>

</html>