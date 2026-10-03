<?php

$conn = mysqli_connect("localhost", "root", "", "lab_automation");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database Connected Successfully!";

?>

