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
     WHERE status IN ('Pending Testing', 'Testing In Progress', 'Ready for Retest')"
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
     WHERE status IN ('Passed', 'CPRI Ready', 'Handed to CPRI')"
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
    WHERE status = 'Failed - Re-manufacturing'"
);

$failed_row = mysqli_fetch_assoc($failed_query);
$failed_products = $failed_row['total'];


/* =========================
   USER INFORMATION
========================= */

require_once __DIR__ . "/config/security.php";
app_start_session();

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
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>

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


<link rel="stylesheet" href="assets/css/pages/products.css">

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


        <?php if (in_array((string) ($_SESSION['role'] ?? ''), ['Administrator', 'Lab Manager', 'Quality Control'], true)): ?>
            <a href="product-workflow.php" class="add-btn">Product Workflow</a>
        <?php endif; ?>

        <?php if (in_array((string) ($_SESSION['role'] ?? ''), ['Administrator', 'Lab Manager'], true)): ?>
            <a href="add-product.php" class="add-btn">+ Add New Product</a>
        <?php endif; ?>

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
                CPRI Ready / Sent
            </div>

            <div class="stat-number">

                <?php
                echo $passed_products;
                ?>

            </div>

            <div class="stat-small">
                All required tests passed / handed off
            </div>

        </div>


        <!-- FAILED -->

        <div class="stat-card">

            <div class="stat-label">
                Re-manufacture
            </div>

            <div class="stat-number">

                <?php
                echo $failed_products;
                ?>

            </div>

            <div class="stat-small">
                Awaiting rework and release for retest
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