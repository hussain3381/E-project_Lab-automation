<?php

include "db.php";

// Apply the minimum role boundary for this module.
require_roles(['Administrator', 'Lab Manager']);

$message = "";


/* =========================
   SAVE SETTINGS
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $lab_name   = trim($_POST["lab_name"]);
    $department = trim($_POST["department"]);
    $admin_name = trim($_POST["admin_name"]);
    $email      = trim($_POST["email"]);
    $contact    = trim($_POST["contact"]);


    /* Check settings table */

    $check_table = mysqli_query(
        $conn,
        "SHOW TABLES LIKE 'settings'"
    );


    /* =========================
       CREATE TABLE IF NOT EXISTS
    ========================= */

    if (mysqli_num_rows($check_table) == 0) {

        mysqli_query($conn, "
            CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lab_name VARCHAR(150),
                department VARCHAR(150),
                admin_name VARCHAR(100),
                email VARCHAR(150),
                contact VARCHAR(50)
            )
        ");


        $stmt = mysqli_prepare($conn, "
            INSERT INTO settings
            (lab_name, department, admin_name, email, contact)
            VALUES (?, ?, ?, ?, ?)
        ");


        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $lab_name,
            $department,
            $admin_name,
            $email,
            $contact
        );


        mysqli_stmt_execute($stmt);

        $message = "Settings saved successfully!";

    }


    /* =========================
       UPDATE / INSERT
    ========================= */

    else {

        $check = mysqli_query(
            $conn,
            "SELECT id FROM settings LIMIT 1"
        );


        if (mysqli_num_rows($check) > 0) {

            $row = mysqli_fetch_assoc($check);

            $settings_id = $row["id"];


            $stmt = mysqli_prepare($conn, "
                UPDATE settings
                SET lab_name = ?,
                    department = ?,
                    admin_name = ?,
                    email = ?,
                    contact = ?
                WHERE id = ?
            ");


            mysqli_stmt_bind_param(
                $stmt,
                "sssssi",
                $lab_name,
                $department,
                $admin_name,
                $email,
                $contact,
                $settings_id
            );


            mysqli_stmt_execute($stmt);

        }

        else {

            $stmt = mysqli_prepare($conn, "
                INSERT INTO settings
                (lab_name, department, admin_name, email, contact)
                VALUES (?, ?, ?, ?, ?)
            ");


            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $lab_name,
                $department,
                $admin_name,
                $email,
                $contact
            );


            mysqli_stmt_execute($stmt);
        }


        $message = "Settings updated successfully!";
    }
}


/* =========================
   DEFAULT SETTINGS
========================= */

$lab_name   = "Lab Automation System";
$department = "Electrical Testing Laboratory";
$admin_name = "Lab Administrator";
$email      = "admin@labautomation.com";
$contact    = "+92 300 0000000";


/* =========================
   LOAD SAVED SETTINGS
========================= */

$check_table = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'settings'"
);


if (mysqli_num_rows($check_table) > 0) {

    $result = mysqli_query(
        $conn,
        "SELECT * FROM settings LIMIT 1"
    );


    if (mysqli_num_rows($result) > 0) {

        $settings = mysqli_fetch_assoc($result);

        $lab_name   = $settings["lab_name"];
        $department = $settings["department"];
        $admin_name = $settings["admin_name"];
        $email      = $settings["email"];
        $contact    = $settings["contact"];
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Settings | Lab Automation</title>


<!-- GOOGLE FONTS -->

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
      rel="stylesheet">


<link rel="stylesheet" href="assets/css/pages/settings.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        </div>

        <div>

            <div class="brand-text">
                LAB AUTOMATION
            </div>

            <div class="brand-subtitle">
                Electrical Testing
            </div>

        </div>

    </div>


    <!-- NAV TITLE -->

    <div class="nav-title">
        Main Menu
    </div>


    <!-- NAVIGATION -->

    <nav class="nav">


        <a href="dashboard.php"
           class="nav-link">

            <span class="nav-icon">⌂</span>

            <span>Dashboard</span>

        </a>


        <a href="products.php"
           class="nav-link">

            <span class="nav-icon">▣</span>

            <span>Products</span>

        </a>


        <a href="testing.php"
           class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-flask" aria-hidden="true"></i></span>

            <span>Testing</span>

        </a>


        <a href="test-types.php"
           class="nav-link">

            <span class="nav-icon">◈</span>

            <span>Test Types</span>

        </a>


        <?php if (($_SESSION['role'] ?? '') === 'Administrator'): ?>
        <a href="product-catalog.php" class="nav-link">
            <span class="nav-icon">▦</span>
            <span>Product Catalog</span>
        </a>
        <a href="departments.php" class="nav-link">
            <span class="nav-icon">⌖</span>
            <span>Departments</span>
        </a>
        <a href="product-test-plan.php" class="nav-link">
            <span class="nav-icon">✓</span>
            <span>Product Test Plans</span>
        </a>
        <a href="product-workflow.php" class="nav-link">
            <span class="nav-icon">⇢</span>
            <span>Product Workflow</span>
        </a>
        <?php endif; ?>


        <a href="testing-status.php"
           class="nav-link">

            <span class="nav-icon">◷</span>

            <span>Testing Status</span>

        </a>


        <a href="search.php"
           class="nav-link">

            <span class="nav-icon">⌕</span>

            <span>Advanced Search</span>

        </a>


        <a href="reports.php"
           class="nav-link">

            <span class="nav-icon">▤</span>

            <span>Reports</span>

        </a>


        <a href="testers.php"
           class="nav-link">

            <span class="nav-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>

            <span>Testers</span>

        </a>


        <a href="settings.php"
           class="nav-link active">

            <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>

            <span>Settings</span>

        </a>


    </nav>


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">

        <div class="user-box">

            <div class="user-avatar">
                LA
            </div>

            <div class="user-info">

                <strong>
                    Lab Administrator
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">

        <h2>
            System Settings
        </h2>

        <p>
            Manage laboratory system configuration
        </p>

    </div>


    <!-- MESSAGE -->

    <?php if ($message != ""): ?>

        <div class="message">

            ✓
            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- SETTINGS CARD -->

    <div class="settings-card">


        <div class="card-title">

            <h3>
                Laboratory Configuration
            </h3>

            <p>
                Update basic information of the Lab Automation System.
            </p>

        </div>


        <form method="POST">
            <!-- Session-bound token required by the shared POST security check. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">


            <div class="form-grid">


                <!-- LAB NAME -->

                <div class="form-group full">

                    <label>
                        Laboratory Name
                    </label>

                    <input
                        type="text"
                        name="lab_name"
                        value="<?php echo htmlspecialchars($lab_name); ?>"
                        placeholder="Enter laboratory name"
                        required
                    >

                </div>


                <!-- DEPARTMENT -->

                <div class="form-group">

                    <label>
                        Department
                    </label>

                    <input
                        type="text"
                        name="department"
                        value="<?php echo htmlspecialchars($department); ?>"
                        placeholder="Enter department"
                        required
                    >

                </div>


                <!-- ADMIN -->

                <div class="form-group">

                    <label>
                        Administrator Name
                    </label>

                    <input
                        type="text"
                        name="admin_name"
                        value="<?php echo htmlspecialchars($admin_name); ?>"
                        placeholder="Enter administrator name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="admin@example.com"
                        required
                    >

                </div>


                <!-- CONTACT -->

                <div class="form-group">

                    <label>
                        Contact Number
                    </label>

                    <input
                        type="text"
                        name="contact"
                        value="<?php echo htmlspecialchars($contact); ?>"
                        placeholder="+92 300 0000000"
                        required
                    >

                </div>


            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Settings
                </button>


                <button
                    type="reset"
                    class="reset-btn"
                >
                    Reset
                </button>

            </div>


        </form>

    </div>


    <!-- =========================
         INFO CARDS
    ========================= -->

    <div class="info-grid">


        <!-- CARD 1 -->

        <div class="info-card">

            <div class="icon">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </div>

            <h4>
                System Configuration
            </h4>

            <p>
                Configure basic laboratory information used throughout
                the automation system.
            </p>

        </div>


        <!-- CARD 2 -->

        <div class="info-card">

            <div class="icon">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
            </div>

            <h4>
                Testing Laboratory
            </h4>

            <p>
                Manage laboratory department and administrator
                information from one place.
            </p>

        </div>


        <!-- CARD 3 -->

        <div class="info-card">

            <div class="icon">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            </div>

            <h4>
                System Security
            </h4>

            <p>
                User accounts and access permissions can be managed
                from the Users module.
            </p>

        </div>


    </div>


</main>


</body>

</html>