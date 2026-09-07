<?php
$host = "e4skgkwwk0s0gkso48oc40kg"; // Internal host for production
if ((isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1') || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == '0.0.0.0') || php_sapi_name() == 'cli-server') {
    $host = "178.16.137.167"; // Remote connection from local PC
}

$con=mysqli_connect($host,"u941015828_sadhuvandna","Sadhuvandna7832%^","u941015828_sadhuvandna",3307);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
mysqli_set_charset($con, "utf8mb4");

// EXTRA safety
mysqli_query($con, "SET NAMES utf8mb4");
mysqli_query($con, "SET CHARACTER SET utf8mb4");
mysqli_query($con, "SET SESSION collation_connection = utf8mb4_unicode_ci");
$con->query("SET time_zone = '+05:30'");
?>
