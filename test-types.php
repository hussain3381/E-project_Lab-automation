<?php

session_start();

include "db.php";

$message = "";
$message_type = "";


/* =========================
   ADD TEST TYPE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $test_code = trim($_POST['test_code']);
    $test_name = trim($_POST['test_name']);
    $department = trim($_POST['department']);
    $description = trim($_POST['description']);

    if (empty($test_code) || empty($test_name)) {

        $message = "Test Code and Test Name are required.";
        $message_type = "error";

    } else {

        $check_sql = "SELECT id FROM test_types WHERE test_code = ?";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $test_code
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {

            $message = "This Test Code already exists.";
            $message_type = "error";

        } else {

            $sql = "INSERT INTO test_types
                    (test_code, test_name, department, description)
                    VALUES (?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $test_code,
                $test_name,
                $department,
                $description
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Test Type added successfully!";
                $message_type = "success";

            } else {

                $message = "Error adding Test Type: " .
                           mysqli_stmt_error($stmt);

                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}


/* =========================
   GET TEST TYPES
========================= */

$sql = "SELECT * FROM test_types ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================
   USER INFO
========================= */

$user_name = $_SESSION['name'] ?? 'Lab Administrator';

$user_role = $_SESSION['role'] ?? 'Administrator';


$name_parts = explode(" ", trim($user_name));

$user_initials = "";

foreach ($name_parts as $part) {

    if ($part != "") {

        $user_initials .= strtoupper(
            substr($part, 0, 1)
        );
    }

    if (strlen($user_initials) >= 2) {
        break;
    }
}

if ($user_initials == "") {
    $user_initials = "LA";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

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

    background: #071014;

    color: #eefafa;

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
        1px solid rgba(72, 215, 196, 0.10);

    padding: 25px 16px;

    display: flex;

    flex-direction: column;

    z-index: 10;
}


/* =========================
   BRAND
========================= */

.brand {

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 0 10px;

    margin-bottom: 30px;
}


.brand-icon {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background:
        rgba(72, 215, 196, 0.09);

    border:
        1px solid rgba(72, 215, 196, 0.14);

    display: flex;

    align-items: center;

    justify-content: center;

    color: #48d7c4;

    font-size: 19px;
}


.brand-text {

    line-height: 1.2;
}


.brand-text strong {

    display: block;

    font-family: "Space Grotesk", sans-serif;

    font-size: 14px;

    letter-spacing: 0.5px;
}


.brand-text span {

    display: block;

    color: #536b6d;

    font-size: 9px;

    margin-top: 4px;
}


/* =========================
   NAV TITLE
========================= */

.nav-title {

    color: #526a6c;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1.5px;

    text-transform: uppercase;

    padding: 0 12px;

    margin: 10px 0 10px;
}


/* =========================
   NAV LINKS
========================= */

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    height: 45px;

    padding: 0 13px;

    margin-bottom: 5px;

    color: #829799;

    text-decoration: none;

    border-radius: 10px;

    font-size: 12px;

    transition: 0.25s ease;
}


.nav-icon {

    width: 20px;

    text-align: center;

    font-size: 14px;
}


.nav-link:hover {

    color: #dffefa;

    background:
        rgba(72, 215, 196, 0.06);
}


.nav-link.active {

    color: #48d7c4;

    background:
        rgba(72, 215, 196, 0.09);

    border:
        1px solid rgba(72, 215, 196, 0.10);
}


/* =========================
   USER BOX
========================= */

.sidebar-bottom {

    margin-top: auto;

    padding: 14px 10px;

    border-top:
        1px solid rgba(255,255,255,0.05);
}


.user-box {

    display: flex;

    align-items: center;

    gap: 10px;
}


.user-avatar {

    width: 35px;

    height: 35px;

    border-radius: 10px;

    background: #48d7c4;

    color: #061110;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

    font-size: 12px;
}


.user-info strong {

    display: block;

    font-size: 11px;
}


.user-info span {

    color: #536b6d;

    font-size: 9px;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 245px;

    min-height: 100vh;

    padding: 30px 35px;

    background:
        radial-gradient(
            circle at 80% 10%,
            rgba(72, 215, 196, 0.045),
            transparent 30%
        );
}


/* =========================
   TOP BAR
========================= */

.topbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 35px;
}


.welcome small {

    color: #48d7c4;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1.5px;

    text-transform: uppercase;
}


.welcome h1 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;

    margin-top: 6px;
}


.welcome p {

    color: #62797b;

    font-size: 12px;

    margin-top: 6px;
}


/* =========================
   ADD BUTTON
========================= */

.add-btn {

    border: none;

    background: #48d7c4;

    color: #061110;

    padding: 11px 15px;

    border-radius: 10px;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.25s ease;
}


.add-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 25px rgba(72,215,196,0.12);
}


/* =========================
   MESSAGE
========================= */

.message {

    padding: 13px 15px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 11px;
}


.message.success {

    background:
        rgba(72,215,196,0.07);

    border:
        1px solid rgba(72,215,196,0.15);

    color: #48d7c4;
}


.message.error {

    background:
        rgba(255,80,90,0.08);

    border:
        1px solid rgba(255,80,90,0.18);

    color: #ff858c;
}


/* =========================
   STATS
========================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    min-height: 125px;

    padding: 21px;

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    transition: 0.25s ease;
}


.stat-card:hover {

    transform: translateY(-3px);

    border-color:
        rgba(72,215,196,0.18);
}


.stat-card::after {

    content: "";

    position: absolute;

    width: 90px;

    height: 90px;

    right: -40px;

    bottom: -45px;

    border-radius: 50%;

    background:
        rgba(72,215,196,0.06);
}


.stat-card span {

    color: #688082;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1px;
}


.stat-card h2 {

    color: #eefafa;

    margin-top: 16px;

    font-family: "Space Grotesk", sans-serif;

    font-size: 29px;
}


/* =========================
   FORM CARD
========================= */

.form-card {

    display: none;

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 25px;

    margin-bottom: 25px;
}


.form-card.show {

    display: block;
}


.form-card h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 17px;

    margin-bottom: 6px;
}


.form-subtitle {

    color: #62797b;

    font-size: 10px;

    margin-bottom: 22px;
}


.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


.form-group.full {

    grid-column: 1 / -1;
}


.form-group label {

    color: #a9c0bf;

    font-size: 10px;

    margin-bottom: 8px;
}


.form-group input,
.form-group select,
.form-group textarea {

    background:
        rgba(255,255,255,0.025);

    border:
        1px solid rgba(255,255,255,0.06);

    color: #e5f1ef;

    padding: 12px;

    border-radius: 10px;

    outline: none;

    font-family: "Inter", sans-serif;

    font-size: 11px;
}


.form-group select option {

    background: #0b1a1e;

    color: #eefafa;
}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    border-color:
        rgba(72,215,196,0.40);

    background:
        rgba(72,215,196,0.025);
}


.form-group textarea {

    min-height: 90px;

    resize: vertical;
}


.form-actions {

    margin-top: 20px;

    display: flex;

    justify-content: flex-end;

    gap: 10px;
}


.cancel-btn {

    border:
        1px solid rgba(255,255,255,0.08);

    background:
        rgba(255,255,255,0.025);

    color: #9bb1b0;

    padding: 11px 18px;

    border-radius: 10px;

    cursor: pointer;

    font-size: 11px;
}


.save-btn {

    border: none;

    background: #48d7c4;

    color: #061110;

    padding: 11px 20px;

    border-radius: 10px;

    font-weight: 700;

    cursor: pointer;

    font-size: 11px;
}


/* =========================
   TABLE CARD
========================= */

.table-card {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow: hidden;
}


.table-header {

    padding: 20px 21px;

    border-bottom:
        1px solid rgba(255,255,255,0.05);
}


.table-header h2 {

    font-family: "Space Grotesk", sans-serif;

    font-size: 14px;
}


.table-header p {

    color: #62797b;

    font-size: 10px;

    margin-top: 5px;
}


.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 850px;
}


th {

    background:
        rgba(255,255,255,0.018);

    color: #688082;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    padding: 14px;

    text-align: left;

    font-weight: 700;
}


td {

    padding: 15px 14px;

    border-top:
        1px solid rgba(255,255,255,0.04);

    font-size: 11px;

    color: #cbdad8;
}


tr:hover {

    background:
        rgba(72,215,196,0.025);
}


/* =========================
   CODE
========================= */

.code {

    color: #48d7c4;

    font-weight: 700;
}


/* =========================
   DEPARTMENT
========================= */

.department {

    color: #9eb8b6;
}


/* =========================
   DESCRIPTION
========================= */

.description {

    color: #718b8d;

    max-width: 300px;
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 40px;

    color: #718b8d;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1000px) {

    .stats {

        grid-template-columns:
            repeat(3, 1fr);
    }

}


@media(max-width: 850px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;

        padding: 25px;
    }

    .stats {

        grid-template-columns: 1fr;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .form-group.full {

        grid-column: auto;
    }

}


@media(max-width: 650px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .sidebar-bottom {

        margin-top: 20px;
    }

    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
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

                    <select name="department">

                        <option value="">
                            Select Department
                        </option>

                        <option value="Electrical Testing">
                            Electrical Testing
                        </option>

                        <option value="Safety Testing">
                            Safety Testing
                        </option>

                        <option value="Performance Testing">
                            Performance Testing
                        </option>

                        <option value="Quality Testing">
                            Quality Testing
                        </option>

                    </select>

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