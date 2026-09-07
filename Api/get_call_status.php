<?php
include("connection.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json");

$call_id = $_GET['call_id'] ?? 0;

if (!$call_id) {
    echo json_encode(["status" => false, "message" => "Missing Call ID"]);
    exit;
}

$q = $con->query("SELECT status FROM tbl_calls WHERE id='$call_id'");

if ($q && $q->num_rows > 0) {
    $row = $q->fetch_assoc();
    echo json_encode([
        "status" => true, 
        "call_status" => $row['status']
    ]);
} else {
    echo json_encode([
        "status" => false, 
        "message" => "Call not found"
    ]);
}
?>
