<?php
include("connection.php");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

date_default_timezone_set("Asia/Kolkata");

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$call_id = intval($_REQUEST['call_id'] ?? $data['call_id'] ?? 0);
$status  = $_REQUEST['status'] ?? $data['status'] ?? '';

$allowed_status = ['accepted', 'rejected', 'ended'];

if (!$call_id || !in_array($status, $allowed_status)) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid parameters"
    ]);
    exit;
}

try {
    $stmt = $con->prepare("
        UPDATE tbl_calls 
        SET status = ?, 
            updated_at = NOW()
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
} catch (Exception $e) {
    echo json_encode(["status" => false, "message" => $e->getMessage()]);
}
?>
