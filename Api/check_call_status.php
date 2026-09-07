<?php
include("connection.php");
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(0);

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$channel_id = $_REQUEST['channel_id'] ?? $data['channel_id'] ?? '';

if (!$channel_id) {
    echo json_encode(["status" => "error", "message" => "Missing channel_id"]);
    exit;
}

try {
    // Check if call exists and get status
    $stmt = $con->prepare("SELECT status FROM tbl_calls WHERE caller_peer_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $channel_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
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
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
