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
$channel_id = $_REQUEST['channel_id'] ?? $data['channel_id'] ?? '';
$status  = $_REQUEST['status'] ?? $data['status'] ?? '';

$allowed_status = ['accepted', 'rejected', 'ended', 'ringing'];

if ((!$call_id && !$channel_id) || !in_array($status, $allowed_status)) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid parameters"
    ]);
    exit;
}

try {
    if ($call_id) {
        $stmt = $con->prepare("UPDATE tbl_calls SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $call_id);
    } else {
        // Use channel_id (caller_peer_id)
        $stmt = $con->prepare("UPDATE tbl_calls SET status = ?, updated_at = NOW() WHERE caller_peer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("ss", $status, $channel_id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            "status" => true,
            "message" => "Call status updated",
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
