<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Suppress errors for production unless needed for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
$con=mysqli_connect("e4skgkwwk0s0gkso48oc40kg","u941015828_sadhuvandna","Sadhuvandna7832%^","u941015828_sadhuvandna",3306);
mysqli_set_charset($con, "utf8mb4");
mysqli_query($con, "SET NAMES utf8mb4");
mysqli_query($con, "SET CHARACTER SET utf8mb4");
mysqli_query($con, "SET SESSION collation_connection = utf8mb4_unicode_ci");
date_default_timezone_set('Asia/Kolkata');
?>



