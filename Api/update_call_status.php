<?php
include("connection.php");

header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

$call_id = $_POST['call_id'] ?? 0;
$status  = $_POST['status'] ?? '';

$allowed_status = ['accepted', 'rejected', 'ended'];

if (!$call_id || !in_array($status, $allowed_status)) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid parameters"
    ]);
    exit;
}

$stmt = $con->prepare("
    UPDATE tbl_calls 
    SET status = ? 
    WHERE id = ?
");

$stmt->bind_param("si", $status, $call_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Call status updated",
        "call_id" => $call_id,
        "new_status" => $status
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Database update failed"
    ]);
}
?>
