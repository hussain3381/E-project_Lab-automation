<?php

include "db.php";

/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* =========================
   PRODUCT QUERY
========================= */

if ($search != "") {

    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM products
        WHERE product_id LIKE ?
        OR product_code LIKE ?
        OR product_name LIKE ?
        OR product_type LIKE ?
        OR revision LIKE ?
        OR status LIKE ?
        ORDER BY id DESC
    ");

    $like = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $result = mysqli_query(
        $conn,
        "SELECT * FROM products ORDER BY id DESC"
    );
}


/* =========================
   TOTAL PRODUCTS
========================= */

$total_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$total_row = mysqli_fetch_assoc($total_query);
$total_products = $total_row['total'];


/* =========================
   PENDING PRODUCTS
========================= */

$pending_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM products
     WHERE status = 'Pending Testing'"
);

$pending_row = mysqli_fetch_assoc($pending_query);
$pending_products = $pending_row['total'];


/* =========================
   PASSED PRODUCTS
========================= */

$passed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM products
     WHERE status LIKE '%Pass%'"
);

$passed_row = mysqli_fetch_assoc($passed_query);
$passed_products = $passed_row['total'];


/* =========================
   FAILED PRODUCTS
========================= */

$failed_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
    FROM products
    WHERE status LIKE '%Fail%'"
);

$failed_row = mysqli_fetch_assoc($failed_query);
$failed_products = $failed_row['total'];


/* =========================
   USER INFORMATION
========================= */

session_start();

$user_name = $_SESSION['name'] ?? 'Lab Administrator';
$user_role = $_SESSION['role'] ?? 'Administrator';


/* User initials */

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

<title>Products | Lab Automation</title>


<!-- =========================
     GOOGLE FONTS
========================= -->

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
   NAVIGATION TITLE
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
   NAVIGATION
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
   SIDEBAR USER
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
   HEADER
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


.add-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 11px 15px;

    border-radius: 10px;

    background: #48d7c4;

    color: #061110;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    transition: 0.25s ease;
}


.add-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 25px rgba(72,215,196,0.12);
}


/* =========================
   STATISTICS
========================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    min-height: 145px;

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

    width: 100px;

    height: 100px;

    right: -45px;

    bottom: -50px;

    border-radius: 50%;

    background:
        rgba(72,215,196,0.06);
}


.stat-label {

    color: #688082;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1px;
}


.stat-number {

    font-family: "Space Grotesk", sans-serif;

    font-size: 31px;

    margin-top: 18px;
}


.stat-small {

    margin-top: 7px;

    font-size: 9px;

    color: #587173;
}


.stat-small span {

    color: #48d7c4;
}


/* =========================
   SEARCH PANEL
========================= */

.search-box {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 20px;
}


.search-form {

    display: flex;

    gap: 10px;
}


.search-form input {

    flex: 1;

    height: 43px;

    background:
        rgba(255,255,255,0.025);

    border:
        1px solid rgba(255,255,255,0.06);

    color: #eefafa;

    padding: 0 14px;

    border-radius: 10px;

    outline: none;

    font-size: 11px;

    font-family: "Inter", sans-serif;
}


.search-form input::placeholder {

    color: #536b6d;
}


.search-form input:focus {

    border-color:
        rgba(72,215,196,0.35);

    background:
        rgba(72,215,196,0.025);
}


.search-btn {

    height: 43px;

    border: none;

    background: #48d7c4;

    color: #061110;

    padding: 0 20px;

    border-radius: 10px;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    font-family: "Inter", sans-serif;
}


.search-btn:hover {

    box-shadow:
        0 7px 20px rgba(72,215,196,0.10);
}


.clear-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    background:
        rgba(255,255,255,0.035);

    color: #829799;

    padding: 0 16px;

    border-radius: 10px;

    font-size: 11px;

    border:
        1px solid rgba(255,255,255,0.05);
}


/* =========================
   TABLE PANEL
========================= */

.table-box {

    background: #0b1a1e;

    border:
        1px solid rgba(255,255,255,0.055);

    border-radius: 15px;

    overflow-x: auto;
}


.table-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

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


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;
}


th {

    background:
        rgba(255,255,255,0.018);

    color: #688082;

    padding: 14px 15px;

    text-align: left;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}


td {

    padding: 15px;

    border-top:
        1px solid rgba(255,255,255,0.04);

    color: #b9c9c8;

    font-size: 11px;
}


tr:hover {

    background:
        rgba(72,215,196,0.025);
}


/* =========================
   PRODUCT ID
========================= */

.product-id {

    color: #48d7c4;

    font-weight: 600;
}


/* =========================
   STATUS
========================= */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 9px;

    background:
        rgba(72,215,196,0.07);

    color: #48d7c4;

    font-size: 9px;

    font-weight: 600;
}


/* =========================
   ACTION BUTTONS
========================= */

.action-buttons {

    display: flex;

    gap: 7px;
}


.view-btn,
.edit-btn {

    display: inline-block;

    text-decoration: none;

    padding: 7px 11px;

    border-radius: 8px;

    font-size: 10px;

    transition: 0.2s ease;
}


.view-btn {

    color: #48d7c4;

    background:
        rgba(72,215,196,0.07);

    border:
        1px solid rgba(72,215,196,0.12);
}


.view-btn:hover {

    background:
        rgba(72,215,196,0.14);
}


.edit-btn {

    color: #829799;

    background:
        rgba(255,255,255,0.025);

    border:
        1px solid rgba(255,255,255,0.06);
}


.edit-btn:hover {

    color: #dffefa;

    background:
        rgba(255,255,255,0.05);
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 50px;

    color: #62797b;
}


.empty-icon {

    font-size: 35px;

    margin-bottom: 12px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1200px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media(max-width: 950px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;

        padding: 25px;
    }
}


@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .stats {

        grid-template-columns: 1fr 1fr;
    }

    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .search-form {

        flex-direction: column;
    }

    .clear-btn {

        padding: 12px;
    }
}


@media(max-width: 450px) {

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
            class="nav-link active"
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
            class="nav-link"
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
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- =========================
         HEADER
    ========================= -->

    <div class="topbar">

        <div class="welcome">

            <small>
                PRODUCT MANAGEMENT
            </small>

            <h1>
                Products
            </h1>

            <p>
                View and manage all registered electrical products.
            </p>

        </div>


        <a
            href="add-product.php"
            class="add-btn"
        >

            + Add New Product

        </a>

    </div>



    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


        <!-- TOTAL -->

        <div class="stat-card">

            <div class="stat-label">
                Total Products
            </div>

            <div class="stat-number">

                <?php
                echo $total_products;
                ?>

            </div>

            <div class="stat-small">
                Registered Products
            </div>

        </div>


        <!-- PENDING -->

        <div class="stat-card">

            <div class="stat-label">
                Pending Testing
            </div>

            <div class="stat-number">

                <?php
                echo $pending_products;
                ?>

            </div>

            <div class="stat-small">
                Awaiting Laboratory Test
            </div>

        </div>


        <!-- PASSED -->

        <div class="stat-card">

            <div class="stat-label">
                Passed
            </div>

            <div class="stat-number">

                <?php
                echo $passed_products;
                ?>

            </div>

            <div class="stat-small">
                Testing Passed
            </div>

        </div>


        <!-- FAILED -->

        <div class="stat-card">

            <div class="stat-label">
                Failed
            </div>

            <div class="stat-number">

                <?php
                echo $failed_products;
                ?>

            </div>

            <div class="stat-small">
                Requires Attention
            </div>

        </div>


    </div>



    <!-- =========================
         SEARCH
    ========================= -->

    <div class="search-box">


        <form
            method="GET"
            action="products.php"
            class="search-form"
        >


            <input
                type="text"
                name="search"
                placeholder="Search Product ID, Code, Name, Type, Revision or Status..."
                value="<?php
                    echo htmlspecialchars($search);
                ?>"
            >


            <button
                type="submit"
                class="search-btn"
            >
                Search
            </button>


            <?php if ($search != ""): ?>

                <a
                    href="products.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>


        </form>

    </div>



    <!-- =========================
         PRODUCT TABLE
    ========================= -->

    <div class="table-box">


        <div class="table-header">

            <div>

                <h2>
                    Product List
                </h2>

                <p>
                    Complete list of products registered in the laboratory system.
                </p>

            </div>

        </div>



        <table>


            <thead>

                <tr>

                    <th>
                        Product ID
                    </th>

                    <th>
                        Product Name
                    </th>

                    <th>
                        Product Code
                    </th>

                    <th>
                        Product Type
                    </th>

                    <th>
                        Revision
                    </th>

                    <th>
                        Manufacturing No.
                    </th>

                    <th>
                        Manufacturing Date
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>



            <tbody>


            <?php if ($result && mysqli_num_rows($result) > 0): ?>


                <?php while ($row = mysqli_fetch_assoc($result)): ?>


                    <tr>


                        <!-- PRODUCT ID -->

                        <td>

                            <span class="product-id">

                                <?php

                                echo htmlspecialchars(
                                    $row['product_id']
                                );

                                ?>

                            </span>

                        </td>



                        <!-- PRODUCT NAME -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['product_name']
                            );

                            ?>

                        </td>



                        <!-- PRODUCT CODE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['product_code']
                            );

                            ?>

                        </td>



                        <!-- PRODUCT TYPE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['product_type']
                            );

                            ?>

                        </td>



                        <!-- REVISION -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['revision']
                            );

                            ?>

                        </td>



                        <!-- MANUFACTURING NUMBER -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['manufacturing_number']
                            );

                            ?>

                        </td>



                        <!-- MANUFACTURING DATE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['manufacturing_date']
                            );

                            ?>

                        </td>



                        <!-- STATUS -->

                        <td>

                            <span class="status">

                                <?php

                                echo htmlspecialchars(
                                    $row['status']
                                );

                                ?>

                            </span>

                        </td>



                        <!-- ACTION -->

                        <td>

                            <div class="action-buttons">


                                <a
                                    href="product-details.php?id=<?php echo $row['id']; ?>"
                                    class="view-btn"
                                >
                                    View
                                </a>


                                <a
                                    href="edit-product.php?id=<?php echo $row['id']; ?>"
                                    class="edit-btn"
                                >
                                    Edit
                                </a>


                            </div>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="9"
                        class="empty"
                    >

                        <div class="empty-icon">
                            📦
                        </div>

                        No products found.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>


    </div>


</main>


</body>

</html>