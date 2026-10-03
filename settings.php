<?php

include "db.php";

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


<style>

/* =========================
   RESET
========================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;
}


/* =========================
   BODY
========================= */

body {

    font-family: "Inter", sans-serif;

    background:

        radial-gradient(
            circle at top right,
            rgba(72,215,196,0.045),
            transparent 30%
        ),

        #071014;

    color: #e7f8f5;

    min-height: 100vh;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 245px;

    height: 100vh;

    background: #09171b;

    border-right:
        1px solid rgba(255,255,255,0.05);

    padding: 24px 16px;

    z-index: 100;

    display: flex;

    flex-direction: column;
}


/* =========================
   BRAND
========================= */

.brand {

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 0 9px 24px;

    border-bottom:
        1px solid rgba(255,255,255,0.05);

    margin-bottom: 22px;
}


.brand-icon {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background:
        rgba(72,215,196,0.10);

    border:
        1px solid rgba(72,215,196,0.16);

    display: flex;

    align-items: center;

    justify-content: center;

    color: #48d7c4;

    font-size: 19px;
}


.brand-text {

    font-family: "Space Grotesk", sans-serif;

    font-size: 16px;

    font-weight: 700;

    color: #eefcf9;

    letter-spacing: 0.5px;
}


.brand-subtitle {

    color: #607678;

    font-size: 9px;

    margin-top: 3px;

    text-transform: uppercase;

    letter-spacing: 1.2px;
}


/* =========================
   NAV TITLE
========================= */

.nav-title {

    color: #506769;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    padding: 0 10px;

    margin-bottom: 9px;
}


/* =========================
   NAVIGATION
========================= */

.nav {

    display: flex;

    flex-direction: column;

    gap: 4px;
}


.nav-link {

    display: flex;

    align-items: center;

    gap: 11px;

    text-decoration: none;

    color: #829799;

    padding: 10px 11px;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 500;

    border:
        1px solid transparent;

    transition: 0.2s;

    position: relative;
}


.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;

    opacity: 0.9;
}


.nav-link:hover {

    color: #d9efeb;

    background:
        rgba(72,215,196,0.06);
}


.nav-link.active {

    color: #48d7c4;

    background:
        rgba(72,215,196,0.09);

    border:
        1px solid rgba(72,215,196,0.10);
}


.nav-link.active::before {

    content: "";

    position: absolute;

    left: -1px;

    top: 8px;

    bottom: 8px;

    width: 2px;

    border-radius: 2px;

    background: #48d7c4;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 16px;

    border-top:
        1px solid rgba(255,255,255,0.05);
}


.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 9px 8px;
}


.user-avatar {

    width: 32px;

    height: 32px;

    border-radius: 50%;

    background: #48d7c4;

    color: #061110;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;
}


.user-info strong {

    display: block;

    color: #dcefed;

    font-size: 11px;
}


.user-info span {

    display: block;

    color: #5f7779;

    font-size: 9px;

    margin-top: 2px;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 245px;

    padding: 30px 35px;

    min-height: 100vh;
}


/* =========================
   TOP HEADER
========================= */

.topbar {

    margin-bottom: 25px;
}


.topbar h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    font-weight: 700;

    color: #edf9f7;

    margin-bottom: 6px;
}


.topbar p {

    color: #62797b;

    font-size: 12px;
}


/* =========================
   MESSAGE
========================= */

.message {

    max-width: 900px;

    margin-bottom: 20px;

    padding: 12px 15px;

    background:
        rgba(72,215,196,0.07);

    border:
        1px solid rgba(72,215,196,0.18);

    color: #48d7c4;

    border-radius: 9px;

    font-size: 12px;
}


/* =========================
   SETTINGS CARD
========================= */

.settings-card {

    max-width: 900px;

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 25px;

    margin-bottom: 22px;
}


.card-title {

    margin-bottom: 24px;
}


.card-title h3 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #eaf8f5;

    margin-bottom: 6px;
}


.card-title p {

    color: #62797b;

    font-size: 11px;
}


/* =========================
   FORM
========================= */

.form-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


.form-group.full {

    grid-column: 1 / -1;
}


label {

    font-size: 11px;

    color: #849b9d;

    margin-bottom: 7px;

    font-weight: 600;
}


input {

    width: 100%;

    padding: 11px 12px;

    background: #071014;

    border:
        1px solid rgba(255,255,255,0.08);

    border-radius: 8px;

    color: #e4f4f1;

    outline: none;

    font-family: "Inter", sans-serif;

    font-size: 12px;
}


input:focus {

    border-color: #48d7c4;

    box-shadow:
        0 0 0 3px
        rgba(72,215,196,0.07);
}


input::placeholder {

    color: #4f6668;
}


/* =========================
   BUTTONS
========================= */

.buttons {

    margin-top: 24px;

    display: flex;

    gap: 10px;
}


.save-btn {

    border: none;

    padding: 11px 20px;

    background: #48d7c4;

    color: #061110;

    font-weight: 700;

    border-radius: 8px;

    cursor: pointer;

    font-size: 12px;

    transition: 0.2s;
}


.save-btn:hover {

    background: #65e3d3;

    box-shadow:
        0 0 18px
        rgba(72,215,196,0.16);
}


.reset-btn {

    padding: 10px 20px;

    background: transparent;

    border:
        1px solid rgba(255,255,255,0.09);

    color: #829799;

    border-radius: 8px;

    cursor: pointer;

    font-size: 12px;

    transition: 0.2s;
}


.reset-btn:hover {

    border-color:
        rgba(72,215,196,0.35);

    color: #48d7c4;
}


/* =========================
   INFO GRID
========================= */

.info-grid {

    max-width: 900px;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 17px;
}


.info-card {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    padding: 20px;

    border-radius: 13px;
}


.info-card .icon {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(72,215,196,0.07);

    border:
        1px solid rgba(72,215,196,0.10);

    border-radius: 9px;

    font-size: 17px;

    margin-bottom: 12px;
}


.info-card h4 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 13px;

    color: #dcefed;

    margin-bottom: 6px;
}


.info-card p {

    color: #62797b;

    font-size: 10px;

    line-height: 1.6;
}


/* =========================
   SCROLLBAR
========================= */

::-webkit-scrollbar {

    width: 7px;
}


::-webkit-scrollbar-track {

    background: #071014;
}


::-webkit-scrollbar-thumb {

    background: #1b3b3d;

    border-radius: 10px;
}


::-webkit-scrollbar-thumb:hover {

    background: #2c5b5c;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {

    .info-grid {

        grid-template-columns: 1fr 1fr;
    }
}


@media (max-width: 850px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 25px;
    }
}


@media (max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .form-group.full {

        grid-column: auto;
    }

    .info-grid {

        grid-template-columns: 1fr;
    }

    .sidebar-bottom {

        margin-top: 20px;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            ⚡
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

            <span class="nav-icon">⚗</span>

            <span>Testing</span>

        </a>


        <a href="test-types.php"
           class="nav-link">

            <span class="nav-icon">◈</span>

            <span>Test Types</span>

        </a>


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

            <span class="nav-icon">♙</span>

            <span>Testers</span>

        </a>


        <a href="settings.php"
           class="nav-link active">

            <span class="nav-icon">⚙</span>

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
                ⚙️
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
                🧪
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
                🔐
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