<?php

include "db.php";

$message = "";
$message_type = "";


/* ==========================================
   NEW TEST SAVE
========================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_id = trim($_POST['product_id']);
    $test_type_id = intval($_POST['test_type_id']);
    $tester_id = intval($_POST['tester_id']);
    $testing_date = $_POST['testing_date'];
    $criteria = trim($_POST['criteria']);
    $expected_output = trim($_POST['expected_output']);
    $actual_output = trim($_POST['actual_output']);
    $result_value = trim($_POST['result']);
    $status = trim($_POST['status']);
    $remarks = trim($_POST['remarks']);


    /* ==========================================
       GET PRODUCT
    ========================================== */

    $product_stmt = mysqli_prepare(
        $conn,
        "SELECT product_id, product_code, revision, status
         FROM products
         WHERE product_id = ?"
    );

    mysqli_stmt_bind_param(
        $product_stmt,
        "s",
        $product_id
    );

    mysqli_stmt_execute($product_stmt);

    $product_result = mysqli_stmt_get_result($product_stmt);

    $product = mysqli_fetch_assoc($product_result);


    if (!$product) {

        $message = "Selected product was not found.";
        $message_type = "error";

    } else {


        /* ==========================================
           GET TEST CODE
        ========================================== */

        $type_stmt = mysqli_prepare(
            $conn,
            "SELECT test_code
             FROM test_types
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $type_stmt,
            "i",
            $test_type_id
        );

        mysqli_stmt_execute($type_stmt);

        $type_result = mysqli_stmt_get_result($type_stmt);

        $test_type = mysqli_fetch_assoc($type_result);


        if (!$test_type) {

            $message = "Selected test type was not found.";
            $message_type = "error";

        } else {


            /* ==========================================
               GENERATE 12-DIGIT TEST ID

               Format:
               4 digits Product ID
               3 digits Test Code
               5 digits Test Roll Number

               Example:
               1234 + 001 + 00001
               = 123400100001
            ========================================== */

            $product_part = preg_replace(
                '/\D/',
                '',
                $product_id
            );

            $product_part = str_pad(
                $product_part,
                4,
                "0",
                STR_PAD_LEFT
            );

            $product_part = substr(
                $product_part,
                -4
            );


            $test_code_number = preg_replace(
                '/\D/',
                '',
                $test_type['test_code']
            );

            $test_code_number = str_pad(
                $test_code_number,
                3,
                "0",
                STR_PAD_LEFT
            );

            $test_code_number = substr(
                $test_code_number,
                -3
            );


            /* ==========================================
               GET NEXT TEST ROLL NUMBER
            ========================================== */

            $roll_query = mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total
                 FROM tests"
            );

            $roll_row = mysqli_fetch_assoc(
                $roll_query
            );

            $roll_number =
                intval($roll_row['total']) + 1;


            $roll_part = str_pad(
                $roll_number,
                5,
                "0",
                STR_PAD_LEFT
            );


            /* ==========================================
               FINAL 12 DIGIT TEST ID
            ========================================== */

            $test_id =
                $product_part .
                $test_code_number .
                $roll_part;


            /* ==========================================
               CHECK DUPLICATE TEST ID
            ========================================== */

            $duplicate_stmt = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM tests
                 WHERE test_id = ?"
            );

            mysqli_stmt_bind_param(
                $duplicate_stmt,
                "s",
                $test_id
            );

            mysqli_stmt_execute(
                $duplicate_stmt
            );

            $duplicate_result =
                mysqli_stmt_get_result(
                    $duplicate_stmt
                );


            if (mysqli_num_rows($duplicate_result) > 0) {

                $message =
                    "Test ID already exists. Please try again.";

                $message_type = "error";

            } else {


                /* ==========================================
                   INSERT TEST
                ========================================== */

                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO tests
                    (
                        test_id,
                        product_id,
                        test_type_id,
                        tester_id,
                        testing_date,
                        criteria,
                        expected_output,
                        actual_output,
                        result,
                        status,
                        remarks
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );


                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "ssiisssssss",
                    $test_id,
                    $product_id,
                    $test_type_id,
                    $tester_id,
                    $testing_date,
                    $criteria,
                    $expected_output,
                    $actual_output,
                    $result_value,
                    $status,
                    $remarks
                );


                if (mysqli_stmt_execute($insert_stmt)) {


                    /* ==========================================
                       UPDATE PRODUCT STATUS
                    ========================================== */

                    $old_status =
                        $product['status'];


                    if ($result_value == "PASS") {

                        $new_product_status =
                            "Passed";

                    } elseif ($result_value == "FAIL") {

                        $new_product_status =
                            "Failed - Re-manufacturing";

                    } else {

                        $new_product_status =
                            "Testing In Progress";
                    }


                    $update_product = mysqli_prepare(
                        $conn,
                        "UPDATE products
                         SET status = ?
                         WHERE product_id = ?"
                    );


                    mysqli_stmt_bind_param(
                        $update_product,
                        "ss",
                        $new_product_status,
                        $product_id
                    );


                    mysqli_stmt_execute(
                        $update_product
                    );


                    /* ==========================================
                       SAVE HISTORY
                    ========================================== */

                    $history_stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO test_history
                        (
                            test_id,
                            product_id,
                            old_status,
                            new_status,
                            remarks,
                            changed_by
                        )
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );


                    $changed_by = "Lab Administrator";


                    mysqli_stmt_bind_param(
                        $history_stmt,
                        "ssssss",
                        $test_id,
                        $product_id,
                        $old_status,
                        $new_product_status,
                        $remarks,
                        $changed_by
                    );


                    mysqli_stmt_execute(
                        $history_stmt
                    );


                    $message =
                        "Test created successfully! Test ID: "
                        . $test_id;

                    $message_type = "success";


                } else {

                    $message =
                        "Error while creating test: "
                        . mysqli_error($conn);

                    $message_type = "error";
                }
            }
        }
    }
}


/* ==========================================
   GET PRODUCTS
========================================== */

$products = mysqli_query(
    $conn,
    "SELECT product_id, product_name, product_code, revision, status
     FROM products
     ORDER BY id DESC"
);


/* ==========================================
   GET TEST TYPES
========================================== */

$test_types = mysqli_query(
    $conn,
    "SELECT id, test_code, test_name, department
     FROM test_types
     ORDER BY id ASC"
);


/* ==========================================
   GET TESTERS
========================================== */

$testers = mysqli_query(
    $conn,
    "SELECT id, name, department, designation
     FROM testers
     ORDER BY id ASC"
);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Start New Test | Lab Automation</title>


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
    font-family: Arial, sans-serif;

    background: #081514;

    color: white;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;

    left: 0;
    top: 0;

    width: 240px;
    height: 100vh;

    background: #0d211f;

    border-right: 1px solid #1d4440;

    padding: 25px 15px;
}


.logo {
    text-align: center;

    margin-bottom: 35px;
}


.logo h2 {
    color: #38d9c5;

    font-size: 21px;

    letter-spacing: 1px;
}


.logo p {
    color: #829c99;

    font-size: 11px;

    margin-top: 6px;
}


.menu a {
    display: block;

    text-decoration: none;

    color: #a9bfbc;

    padding: 13px 15px;

    margin: 6px 0;

    border-radius: 8px;

    transition: 0.3s;
}


.menu a:hover,
.menu a.active {
    background: #123a35;

    color: #38d9c5;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 240px;

    padding: 35px;

    max-width: 1250px;
}


/* =========================
   HEADER
========================= */

.header {
    margin-bottom: 25px;
}


.header h1 {
    font-size: 28px;
}


.header p {
    color: #829c99;

    margin-top: 7px;

    font-size: 14px;
}


/* =========================
   MESSAGE
========================= */

.message {
    padding: 14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 13px;
}


.success {
    background: #123b34;

    border: 1px solid #287466;

    color: #63e2cf;
}


.error {
    background: #402321;

    border: 1px solid #75403a;

    color: #ff9b91;
}


/* =========================
   FORM CONTAINER
========================= */

.form-box {
    background: #0d211f;

    border: 1px solid #1d4440;

    border-radius: 14px;

    overflow: hidden;
}


/* =========================
   FORM SECTION
========================= */

.form-section {
    padding: 25px;

    border-bottom: 1px solid #1d4440;
}


.section-title {
    color: #38d9c5;

    font-size: 16px;

    margin-bottom: 20px;

    font-weight: bold;
}


.form-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}


.form-group {
    display: flex;

    flex-direction: column;
}


.form-group.full {
    grid-column: 1 / -1;
}


label {
    color: #b5c9c6;

    font-size: 12px;

    margin-bottom: 8px;
}


input,
select,
textarea {
    width: 100%;

    background: #071311;

    border: 1px solid #28514c;

    color: white;

    padding: 12px;

    border-radius: 7px;

    outline: none;

    font-family: Arial, sans-serif;

    font-size: 13px;
}


input:focus,
select:focus,
textarea:focus {
    border-color: #38d9c5;

    box-shadow:
        0 0 0 2px
        rgba(56, 217, 197, 0.08);
}


select option {
    background: #0d211f;

    color: white;
}


textarea {
    min-height: 100px;

    resize: vertical;
}


.required {
    color: #38d9c5;
}


/* =========================
   INFO BOX
========================= */

.info-box {
    background: #102a27;

    border: 1px solid #20514b;

    border-radius: 8px;

    padding: 14px;

    margin-top: 10px;

    color: #91aaa6;

    font-size: 11px;

    line-height: 1.6;
}


/* =========================
   BUTTONS
========================= */

.form-actions {
    padding: 22px 25px;

    display: flex;

    justify-content: flex-end;

    gap: 10px;
}


.cancel-btn {
    text-decoration: none;

    background: #172d2b;

    color: #a9bfbc;

    border: 1px solid #31504c;

    padding: 12px 20px;

    border-radius: 7px;

    font-size: 13px;
}


.save-btn {
    border: none;

    background: #38d9c5;

    color: #061311;

    padding: 12px 23px;

    border-radius: 7px;

    font-weight: bold;

    cursor: pointer;

    font-size: 13px;
}


.save-btn:hover {
    background: #5ce7d6;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 800px) {

    .sidebar {
        width: 200px;
    }

    .main {
        margin-left: 200px;

        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
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

        padding: 15px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .cancel-btn,
    .save-btn {
        text-align: center;

        width: 100%;
    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        <h2>LAB AUTOMATION</h2>

        <p>Electrical Testing System</p>

    </div>


    <div class="menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="testing.php" class="active">
            Testing
        </a>

        <a href="test-types.php">
            Test Types
        </a>

        <a href="search.php">
            Advanced Search
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="testers.php">
            Testers
        </a>

        <a href="settings.php">
            Settings
        </a>

        <a href="login.php">
            Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <div class="header">

        <h1>Start New Test</h1>

        <p>
            Create a new laboratory testing record.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="new-test.php"
    >


        <div class="form-box">


            <!-- =========================
                 TEST INFORMATION
            ========================= -->

            <div class="form-section">

                <div class="section-title">
                    Test Information
                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Product
                            <span class="required">*</span>
                        </label>


                        <select
                            name="product_id"
                            required
                        >

                            <option value="">
                                Select Product
                            </option>


                            <?php while (
                                $product =
                                mysqli_fetch_assoc($products)
                            ): ?>

                                <option
                                    value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_id']
                                    );
                                    ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>


                        <div class="info-box">

                            Select the product that needs
                            laboratory testing.

                        </div>

                    </div>


                    <div class="form-group">

                        <label>
                            Test Type
                            <span class="required">*</span>
                        </label>


                        <select
                            name="test_type_id"
                            required
                        >

                            <option value="">
                                Select Test Type
                            </option>


                            <?php while (
                                $type =
                                mysqli_fetch_assoc($test_types)
                            ): ?>

                                <option
                                    value="<?php echo $type['id']; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $type['test_code']
                                    );
                                    ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $type['test_name']
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Tester
                            <span class="required">*</span>
                        </label>


                        <select
                            name="tester_id"
                            required
                        >

                            <option value="">
                                Select Tester
                            </option>


                            <?php while (
                                $tester =
                                mysqli_fetch_assoc($testers)
                            ): ?>

                                <option
                                    value="<?php echo $tester['id']; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $tester['name']
                                    );
                                    ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $tester['designation']
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Testing Date
                            <span class="required">*</span>
                        </label>


                        <input
                            type="date"
                            name="testing_date"
                            value="<?php echo date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                </div>

            </div>


            <!-- =========================
                 TESTING DETAILS
            ========================= -->

            <div class="form-section">

                <div class="section-title">
                    Testing Details
                </div>


                <div class="form-grid">


                    <div class="form-group full">

                        <label>
                            Testing Criteria
                            <span class="required">*</span>
                        </label>


                        <textarea
                            name="criteria"
                            placeholder="Enter the criteria or standard against which the product will be tested..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Expected Output
                            <span class="required">*</span>
                        </label>


                        <textarea
                            name="expected_output"
                            placeholder="Enter expected testing output..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Actual Output
                            <span class="required">*</span>
                        </label>


                        <textarea
                            name="actual_output"
                            placeholder="Enter actual testing output..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Result
                            <span class="required">*</span>
                        </label>


                        <select
                            name="result"
                            required
                        >

                            <option value="">
                                Select Result
                            </option>

                            <option value="PASS">
                                PASS
                            </option>

                            <option value="FAIL">
                                FAIL
                            </option>

                            <option value="PENDING">
                                PENDING
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Testing Status
                            <span class="required">*</span>
                        </label>


                        <select
                            name="status"
                            required
                        >

                            <option value="Pending">
                                Pending
                            </option>

                            <option value="In Progress">
                                In Progress
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                        </select>

                    </div>


                    <div class="form-group full">

                        <label>
                            Detailed Remarks
                        </label>


                        <textarea
                            name="remarks"
                            placeholder="Enter observations, defects, comments or additional testing remarks..."
                        ></textarea>

                    </div>


                </div>

            </div>


            <!-- =========================
                 BUTTONS
            ========================= -->

            <div class="form-actions">

                <a
                    href="testing.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Test Record
                </button>

            </div>


        </div>

    </form>


</div>


</body>

</html>