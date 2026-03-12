<?php
$con=mysqli_connect("e4skgkwwk0s0gkso48oc40kg","u941015828_sadhuvandna","Sadhuvandna7832%^","u941015828_sadhuvandna",3306);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
mysqli_set_charset($con, "utf8mb4");

// EXTRA safety
mysqli_query($con, "SET NAMES utf8mb4");
mysqli_query($con, "SET CHARACTER SET utf8mb4");
mysqli_query($con, "SET SESSION collation_connection = utf8mb4_unicode_ci");
?>
