<?php

include "db.php";

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

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

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

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Testers | Lab Automation</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
rel="stylesheet"
>


<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: "Inter", sans-serif;

    background:
        radial-gradient(
            circle at top right,
            rgba(72, 215, 196, 0.045),
            transparent 30%
        ),
        #071014;

    color: #e7f5f3;

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

    border-right: 1px solid rgba(255,255,255,0.05);

    padding: 25px 16px;

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

    gap: 12px;

    padding: 0 10px 25px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.brand-icon {

    width: 40px;
    height: 40px;

    border-radius: 10px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: rgba(72,215,196,0.08);

    color: #48d7c4;

    font-size: 20px;

    border: 1px solid rgba(72,215,196,0.12);
}

.brand-text {

    display: flex;

    flex-direction: column;
}

.brand-text strong {

    font-family: "Space Grotesk", sans-serif;

    color: #e8f8f5;

    font-size: 17px;

    letter-spacing: 0.5px;
}

.brand-text span {

    color: #62797b;

    font-size: 10px;

    margin-top: 3px;

    text-transform: uppercase;

    letter-spacing: 1.2px;
}


/* =========================
   NAVIGATION
========================= */

.nav-title {

    font-size: 10px;

    color: #52686a;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    margin: 25px 12px 10px;
}

.nav {

    display: flex;

    flex-direction: column;

    gap: 4px;
}

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    text-decoration: none;

    color: #829799;

    padding: 11px 12px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 500;

    border: 1px solid transparent;

    transition: 0.2s;

    position: relative;
}

.nav-link:hover {

    color: #d9eeeb;

    background: rgba(72,215,196,0.06);
}

.nav-link.active {

    color: #48d7c4;

    background: rgba(72,215,196,0.09);

    border-color: rgba(72,215,196,0.10);
}

.nav-link.active::before {

    content: "";

    position: absolute;

    left: -16px;

    top: 7px;

    bottom: 7px;

    width: 3px;

    background: #48d7c4;

    border-radius: 0 4px 4px 0;
}

.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 16px;

    border-top: 1px solid rgba(255,255,255,0.05);
}

.user-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px 8px;
}

.user-avatar {

    width: 34px;
    height: 34px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #48d7c4;

    color: #061110;

    font-weight: 700;

    font-size: 12px;
}

.user-info strong {

    display: block;

    color: #dcefed;

    font-size: 12px;
}

.user-info span {

    display: block;

    color: #5f7577;

    font-size: 10px;

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
   HEADER
========================= */

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;
}

.header h1 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    color: #edf8f6;

    margin-bottom: 6px;
}

.header p {

    color: #62797b;

    font-size: 13px;
}

.add-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    background: #48d7c4;

    color: #061110;

    border: none;

    padding: 11px 17px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

    transition: 0.2s;
}

.add-btn:hover {

    transform: translateY(-1px);

    filter: brightness(1.05);
}


/* =========================
   ALERTS
========================= */

.message {

    background: rgba(72,215,196,0.08);

    border: 1px solid rgba(72,215,196,0.18);

    color: #48d7c4;

    padding: 13px 16px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 13px;
}

.error {

    background: rgba(255,80,80,0.07);

    border: 1px solid rgba(255,80,80,0.18);

    color: #ff8d8d;

    padding: 13px 16px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 13px;
}


/* =========================
   STATS
========================= */

.stats {

    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 18px;

    margin-bottom: 24px;
}

.stat-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 21px 22px;

    transition: 0.2s;
}

.stat-card:hover {

    border-color: rgba(72,215,196,0.14);
}

.stat-card span {

    color: #62797b;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.7px;
}

.stat-card h2 {

    margin-top: 8px;

    font-family: "Space Grotesk", sans-serif;

    font-size: 28px;

    color: #48d7c4;
}


/* =========================
   FORM CARD
========================= */

.form-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 24px;

    margin-bottom: 24px;
}

.form-card h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #e8f5f3;

    margin-bottom: 20px;
}

.form-grid {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 18px;
}

.field {

    display: flex;

    flex-direction: column;

    gap: 8px;
}

.field label {

    color: #8ca3a3;

    font-size: 11px;

    font-weight: 600;
}

.field input,
.field select {

    width: 100%;

    padding: 11px 12px;

    background: #071014;

    color: #e5f5f2;

    border: 1px solid rgba(255,255,255,0.07);

    border-radius: 8px;

    outline: none;

    font-family: "Inter", sans-serif;

    font-size: 13px;
}

.field input::placeholder {

    color: #4f6668;
}

.field input:focus,
.field select:focus {

    border-color: rgba(72,215,196,0.45);

    box-shadow: 0 0 0 3px rgba(72,215,196,0.06);
}

.form-submit {

    margin-top: 20px;
}

.form-submit button {

    background: #48d7c4;

    border: none;

    color: #061110;

    padding: 10px 18px;

    border-radius: 8px;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;
}

.form-submit button:hover {

    filter: brightness(1.05);

    transform: translateY(-1px);
}


/* =========================
   TABLE
========================= */

.table-card {

    background: #0b1a1e;

    border: 1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}

.table-header {

    padding: 21px 24px;

    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.table-header h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 18px;

    color: #e8f5f3;
}

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 700px;
}

th {

    text-align: left;

    padding: 14px 20px;

    color: #607779;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 0.8px;

    background: #09171b;
}

td {

    padding: 15px 20px;

    border-top: 1px solid rgba(255,255,255,0.045);

    color: #bdcfcc;

    font-size: 13px;
}

tr:hover td {

    background: rgba(72,215,196,0.025);
}

.tester-name {

    color: #edf8f6;

    font-weight: 600;
}

.department {

    color: #48d7c4;

    font-weight: 500;
}

.designation {

    color: #91a5a4;
}

.delete-btn {

    display: inline-block;

    text-decoration: none;

    color: #ff8d8d;

    border: 1px solid rgba(255,90,90,0.18);

    padding: 6px 10px;

    border-radius: 7px;

    font-size: 11px;

    transition: 0.2s;
}

.delete-btn:hover {

    background: rgba(255,80,80,0.07);

    border-color: rgba(255,90,90,0.3);
}

.empty {

    text-align: center;

    padding: 35px;

    color: #617779;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;

        padding: 25px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .sidebar-bottom {

        margin-top: 20px;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .stats {

        grid-template-columns: 1fr;
    }
}

</style>

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

            <span class="nav-icon">♙</span>

            <span>Testers</span>

        </a>


        <a href="settings.php" class="nav-link">

            <span class="nav-icon">⚙</span>

            <span>Settings</span>

        </a>


        <a href="logout.php" class="nav-link">

            <span class="nav-icon">↪</span>

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

                            <a
                                href="testers.php?delete=<?php echo $row['id']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this tester?');"
                            >
                                Delete
                            </a>

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