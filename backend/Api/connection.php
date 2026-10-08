<?php
$host = getenv('DB_HOST') ?: "e4skgkwwk0s0gkso48oc40kg";
$user = getenv('DB_USER') ?: "u941015828_sadhuvandna";
$pass = getenv('DB_PASS') ?: "Sadhuvandna7832%^";
$name = getenv('DB_NAME') ?: "u941015828_sadhuvandna";
$port = getenv('DB_PORT') ?: 3307;

$con = mysqli_connect($host, $user, $pass, $name, $port);
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
