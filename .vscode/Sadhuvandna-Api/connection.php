<?php
// Re-using the same credentials as the legacy project
$con=mysqli_connect("localhost","u941015828_sadhuvandna","Sadhuvandna7832%^","u941015828_sadhuvandna");

if (mysqli_connect_errno()) {
    echo json_encode(["status" => "error", "message" => "Failed to connect to MySQL: " . mysqli_connect_error()]);
    exit();
}
?>
