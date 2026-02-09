<?php
include("connection.php");
header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? 0;
$token   = $_POST['fcm_token'] ?? '';

if (!$user_id || !$token) {
    echo json_encode(["status" => false, "message" => "Missing parameters"]);
    exit;
}

// Update token in tbl_members
$stmt = $con->prepare("UPDATE tbl_members SET fcm_token = ? WHERE id = ?");
$stmt->bind_param("si", $token, $user_id);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Token updated"]);
} else {
    echo json_encode(["status" => false, "message" => "Database error"]);
}
?>
