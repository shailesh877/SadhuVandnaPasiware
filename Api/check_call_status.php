<?php
include("connection.php");
header('Content-Type: application/json');
error_reporting(0);

$channel_id = $_POST['channel_id'] ?? '';

if (!$channel_id) {
    echo json_encode(["status" => "error", "message" => "Missing channel_id"]);
    exit;
}

// Check if call exists and get status
// We use caller_peer_id to match channel_id
$q = $con->query("SELECT status FROM tbl_calls WHERE caller_peer_id = '$channel_id' ORDER BY id DESC LIMIT 1");

if ($q && $q->num_rows > 0) {
    $row = $q->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "call_status" => $row['status']
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Call not found"
    ]);
}
?>
