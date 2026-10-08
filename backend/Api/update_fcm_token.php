<?php
include("connection.php");
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$user_id = intval($_REQUEST['user_id'] ?? $data['user_id'] ?? 0);
$token   = $_REQUEST['fcm_token'] ?? $data['fcm_token'] ?? '';

if (!$user_id || !$token) {
    echo json_encode(["status" => false, "message" => "Missing parameters"]);
    exit;
}

try {
    // Update token in tbl_members
    $stmt = $con->prepare("UPDATE tbl_members SET fcm_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => true, "message" => "Token updated"]);
    } else {
        echo json_encode(["status" => false, "message" => "Database error"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => false, "message" => $e->getMessage()]);
}
?>
